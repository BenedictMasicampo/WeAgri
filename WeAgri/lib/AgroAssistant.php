<?php
declare(strict_types=1);

final class AgroAssistant
{
    private array $knowledgeBase;
    private array $experts;

    private array $stopWords = [
        'a', 'about', 'after', 'all', 'and', 'any', 'are', 'at', 'be', 'been', 'before', 'can', 'could',
        'did', 'do', 'for', 'from', 'get', 'have', 'how', 'i', 'if', 'in', 'into', 'is', 'it', 'my',
        'of', 'on', 'or', 'please', 'should', 'so', 'that', 'the', 'their', 'there', 'this', 'to',
        'was', 'what', 'when', 'why', 'with', 'would', 'your',
    ];

    public function __construct(array $knowledgeBase, array $experts = [])
    {
        $this->knowledgeBase = $knowledgeBase;
        $this->experts = $experts;
    }

    public function answer(string $message, array $context = []): array
    {
        $cleanMessage = trim($message);
        $tokens = $this->tokenize($cleanMessage);
        $matches = $this->rankKnowledge($tokens, $cleanMessage);
        $topMatches = array_slice($matches, 0, 3);
        $category = $context['category'] ?? $this->detectCategory($cleanMessage, $topMatches);
        $crop = $context['crop'] ?? $this->detectCrop($cleanMessage, $topMatches);
        $actions = $this->buildActionList($topMatches);
        $needsExpert = $this->shouldEscalate($cleanMessage, $topMatches, $context);

        return [
            'reply' => $this->composeReply($topMatches, $actions, $needsExpert, $category, $crop),
            'references' => array_values(array_map(
                fn(array $entry): string => $entry['title'] . ' - ' . $entry['source'],
                $topMatches
            )),
            'actions' => $actions,
            'category' => $category,
            'crop' => $crop,
            'confidence' => $topMatches[0]['score'] ?? 0,
            'escalate_to_expert' => $needsExpert,
            'suggested_title' => $this->buildSuggestedTitle($crop, $category, $cleanMessage),
            'suggested_expert_focus' => $this->mapCategoryToSpecialty($category),
        ];
    }

    private function tokenize(string $message): array
    {
        $words = preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($message), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_filter(array_unique($words), function (string $word): bool {
            return mb_strlen($word) > 2 && !in_array($word, $this->stopWords, true);
        }));
    }

    private function rankKnowledge(array $tokens, string $message): array
    {
        $message = mb_strtolower($message);
        $ranked = [];

        foreach ($this->knowledgeBase as $entry) {
            $title = mb_strtolower((string) ($entry['title'] ?? ''));
            $topic = mb_strtolower((string) ($entry['topic'] ?? ''));
            $content = mb_strtolower((string) ($entry['content'] ?? ''));
            $tags = $this->normalizeTags($entry['tags'] ?? []);
            $score = 0;

            foreach ($tokens as $token) {
                if (str_contains($title, $token)) {
                    $score += 5;
                }

                if (str_contains($topic, $token)) {
                    $score += 4;
                }

                if (in_array($token, $tags, true)) {
                    $score += 4;
                }

                if (str_contains($content, $token)) {
                    $score += 2;
                }
            }

            if ($score === 0) {
                foreach ($tags as $tag) {
                    if ($tag !== '' && str_contains($message, $tag)) {
                        $score += 3;
                    }
                }
            }

            if ($score > 0) {
                $entry['score'] = $score;
                $entry['recommendations'] = $this->normalizeRecommendations($entry['recommendations'] ?? []);
                $ranked[] = $entry;
            }
        }

        usort($ranked, fn(array $left, array $right): int => $right['score'] <=> $left['score']);

        return $ranked;
    }

    private function composeReply(
        array $topMatches,
        array $actions,
        bool $needsExpert,
        string $category,
        string $crop
    ): string {
        if ($topMatches === []) {
            return "I do not have a close AgroLLM match for that concern yet.\n\n"
                . "Immediate next steps:\n"
                . "- Capture clear photos of the affected crop area.\n"
                . "- Note when the symptoms started, how fast they are spreading, and any recent weather changes.\n"
                . "- Create a consultation so a human agricultural adviser can review the case.\n\n"
                . "This is a good case for expert assessment because the available knowledge base is not a strong match.";
        }

        $summary = "AgroLLM matched your concern to {$category} guidance";
        if ($crop !== 'General farming') {
            $summary .= " for {$crop}.";
        } else {
            $summary .= '.';
        }

        $guidance = $this->formatLines('Immediate steps', array_slice($actions, 0, 4));
        $references = $this->formatLines('Knowledge used', array_map(
            fn(array $entry): string => $entry['title'] . ' - ' . $entry['source'],
            $topMatches
        ));

        $routing = $needsExpert
            ? "This looks urgent or complex enough that a human agricultural adviser should confirm the diagnosis and next action."
            : "This looks suitable for AI first-level guidance, but you should keep monitoring the crop and escalate if symptoms spread.";

        return trim($summary . "\n\n" . $guidance . "\n\n" . $routing . "\n\n" . $references);
    }

    private function buildActionList(array $topMatches): array
    {
        $actions = [];

        foreach ($topMatches as $entry) {
            foreach ($entry['recommendations'] ?? [] as $recommendation) {
                $cleanRecommendation = trim((string) $recommendation);
                if ($cleanRecommendation !== '' && !in_array($cleanRecommendation, $actions, true)) {
                    $actions[] = $cleanRecommendation;
                }
            }
        }

        if ($actions === []) {
            $actions = [
                'Observe the pattern of damage and document any change over the next 24 hours.',
                'Separate badly affected plants from healthy plants when possible.',
                'Create a consultation so an agricultural expert can verify the diagnosis.',
            ];
        }

        return array_slice($actions, 0, 5);
    }

    private function shouldEscalate(string $message, array $topMatches, array $context): bool
    {
        $urgentWords = [
            'urgent', 'severe', 'critical', 'spreading', 'spread', 'dying', 'dead', 'whole field',
            'entire', 'infestation', 'cannot identify', 'not improving', 'worse', 'wilting badly',
            'many plants', 'need expert', 'unknown',
        ];

        $normalized = mb_strtolower($message);
        $topScore = (float) ($topMatches[0]['score'] ?? 0);
        $urgency = mb_strtolower((string) ($context['urgency'] ?? ''));

        foreach ($urgentWords as $word) {
            if (str_contains($normalized, $word)) {
                return true;
            }
        }

        if (in_array($urgency, ['high', 'critical'], true)) {
            return true;
        }

        if ($topScore < 7) {
            return true;
        }

        return str_word_count($message) > 28 && $topScore < 10;
    }

    private function detectCategory(string $message, array $topMatches): string
    {
        $message = mb_strtolower($message);

        $map = [
            'Pest and Disease' => ['pest', 'worm', 'blight', 'fungus', 'spot', 'mold', 'leaf curl', 'armyworm', 'disease'],
            'Soil Management' => ['soil', 'ph', 'acidity', 'compost', 'salinity'],
            'Crop Nutrition' => ['fertilizer', 'nutrient', 'nitrogen', 'yellowing', 'deficiency'],
            'Water and Irrigation' => ['water', 'flood', 'waterlogging', 'drainage', 'irrigation'],
            'Farming Practices' => ['spacing', 'mulch', 'pruning', 'rotation', 'transplant'],
        ];

        foreach ($map as $label => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($message, $keyword)) {
                    return $label;
                }
            }
        }

        if ($topMatches !== []) {
            return (string) ($topMatches[0]['topic'] ?? 'General Advisory');
        }

        return 'General Advisory';
    }

    private function detectCrop(string $message, array $topMatches): string
    {
        $message = mb_strtolower($message);
        $crops = ['rice', 'corn', 'maize', 'tomato', 'onion', 'banana', 'coconut', 'pechay', 'vegetable'];

        foreach ($crops as $crop) {
            if (str_contains($message, $crop)) {
                return ucfirst($crop);
            }
        }

        foreach ($topMatches as $entry) {
            foreach ($this->normalizeTags($entry['tags'] ?? []) as $tag) {
                if (in_array($tag, $crops, true)) {
                    return ucfirst($tag);
                }
            }
        }

        return 'General farming';
    }

    private function buildSuggestedTitle(string $crop, string $category, string $message): string
    {
        $prefix = $crop !== 'General farming' ? $crop : 'Farm';
        $snippet = trim((string) preg_replace('/\s+/', ' ', $message));
        $snippet = mb_substr($snippet, 0, 42);

        return "{$prefix} {$category}: {$snippet}";
    }

    private function mapCategoryToSpecialty(string $category): string
    {
        return match ($category) {
            'Pest and Disease' => 'Pest Management',
            'Soil Management' => 'Soil Health',
            'Crop Nutrition' => 'Crop Nutrition',
            'Water and Irrigation' => 'Irrigation & Farm Practices',
            default => 'General Agronomy',
        };
    }

    private function normalizeTags(array|string $tags): array
    {
        if (is_string($tags)) {
            $tags = preg_split('/\s*,\s*/', mb_strtolower($tags), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        }

        return array_values(array_filter(array_map(
            fn(string $tag): string => trim(mb_strtolower($tag)),
            $tags
        )));
    }

    private function normalizeRecommendations(array|string $recommendations): array
    {
        if (is_string($recommendations)) {
            $recommendations = preg_split('/\r\n|\r|\n|\|{2}/', $recommendations, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        }

        return array_values(array_filter(array_map(
            fn(string $line): string => trim($line),
            $recommendations
        )));
    }

    private function formatLines(string $title, array $items): string
    {
        if ($items === []) {
            return '';
        }

        $lines = array_map(fn(string $item): string => "- {$item}", $items);

        return $title . ":\n" . implode("\n", $lines);
    }
}
