<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/bootstrap.php';

$initialState = weagri_bootstrap_state();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WeAgri</title>
    <meta
        name="description"
        content="WeAgri is a simple consultation workspace for farmers, consultants, and administrators."
    >
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=20260421-overview-functions">
</head>
<body>
    <header class="site-header" id="home">
        <div class="container nav-row">
            <a class="brand" href="#home">
                <span class="brand-mark">WA</span>
                <span class="brand-copy">
                    <strong>WeAgri</strong>
                    <small>Consultation workspace</small>
                </span>
            </a>

            <button class="nav-toggle" id="nav-toggle" type="button" aria-expanded="false" aria-controls="site-nav">
                Menu
            </button>

            <nav class="site-nav" id="site-nav">
                <a href="#access">Access</a>
                <a href="#consultations">Consultations</a>
                <button type="button" class="button button-secondary nav-cta" data-open-assistant>
                    Ask AI
                </button>
                <div class="nav-user-shell">
                    <span class="source-pill" id="nav-user-chip">Guest</span>
                    <button type="button" class="text-button is-hidden" id="nav-logout-button">Logout</button>
                </div>
            </nav>
        </div>
    </header>

    <main>
        <section class="hero section">
            <div class="container hero-grid">
                <div class="hero-copy reveal">
                    <span class="eyebrow">WeAgri</span>
                    <h1>WeAgri</h1>

                    <div class="project-overview">
                        <div class="overview-row">
                            <span>Overview</span>
                            <strong>A farm consultation system for submitting concerns and managing adviser responses.</strong>
                        </div>
                        <div class="overview-row">
                            <span>Established</span>
                            <strong>April 18, 2026</strong>
                        </div>
                        <div class="overview-row">
                            <span>Functions</span>
                            <ul class="function-list">
                                <li>Account registration and sign-in</li>
                                <li>Farmer consultation submission</li>
                                <li>Consultant response thread</li>
                                <li>Admin assignment and status updates</li>
                                <li>Notifications for case activity</li>
                            </ul>
                        </div>
                    </div>

                    <div class="hero-actions">
                        <a class="button" href="#access">Log in</a>
                        <button type="button" class="button button-secondary" data-open-assistant>
                            Ask AI
                        </button>
                    </div>
                </div>

                <aside class="hero-panel reveal">
                    <div class="panel-surface">
                        <div class="panel-heading-row">
                            <div>
                                <span class="panel-kicker">Status</span>
                                <h2>Consultations</h2>
                            </div>
                            <span class="source-pill is-hidden" id="source-pill">Loading...</span>
                        </div>

                        <div class="signal-grid">
                            <article class="signal-card">
                                <span>Experts</span>
                                <strong id="signal-online-experts">0</strong>
                            </article>
                            <article class="signal-card">
                                <span>Open</span>
                                <strong id="signal-active-consultations">0</strong>
                            </article>
                            <article class="signal-card">
                                <span>Response</span>
                                <strong id="signal-response-time">0m</strong>
                            </article>
                            <article class="signal-card">
                                <span>Alerts</span>
                                <strong id="signal-unread-alerts">0</strong>
                            </article>
                        </div>

                        <div class="live-thread-card">
                            <div class="live-thread-header">
                                <span class="dot"></span>
                                <span>Latest case</span>
                            </div>
                            <h3 id="hero-thread-title">No active consultation yet</h3>
                            <div class="thread-chip-row">
                                <span class="meta-chip" id="hero-thread-status">Waiting for data</span>
                                <span class="meta-chip" id="hero-thread-expert">Expert availability pending</span>
                            </div>
                            <p id="hero-thread-preview">
                                The latest consultation will appear here after loading.
                            </p>
                        </div>
                    </div>
                </aside>
            </div>
        </section>

        <section class="section section-soft" id="access">
            <div class="container auth-layout">
                <section class="panel auth-summary-panel reveal">
                    <div class="panel-heading-row">
                        <div>
                            <span class="panel-kicker">Account access</span>
                            <h2 id="auth-summary-title">Continue as guest</h2>
                        </div>
                        <span class="count-pill" id="auth-role-badge">Guest</span>
                    </div>

                    <p class="hero-text" id="auth-summary-copy">
                        Log in to create consultations, respond as a consultant, or manage assignments as an administrator.
                    </p>

                    <div class="profile-card" id="auth-profile-grid">
                        <div class="profile-avatar" id="auth-initials">G</div>
                        <div class="profile-details">
                            <div class="profile-row">
                                <span>Name</span>
                                <strong id="auth-name">Guest user</strong>
                            </div>
                            <div class="profile-row">
                                <span>Email</span>
                                <strong id="auth-email">Not signed in</strong>
                            </div>
                            <div class="profile-row">
                                <span>Profile</span>
                                <strong id="auth-role-detail">Public access</strong>
                            </div>
                        </div>
                    </div>

                    <div class="stack-list is-hidden" id="auth-capabilities"></div>

                    <button type="button" class="button is-hidden" id="logout-button">Log out</button>
                </section>

                <section class="panel auth-form-panel reveal">
                    <div class="auth-switch">
                        <button type="button" class="text-button is-active" id="show-login">Log in</button>
                        <button type="button" class="text-button" id="show-register">Sign up</button>
                    </div>

                    <form id="login-form" class="auth-form">
                        <label class="field">
                            <span>Email</span>
                            <input type="email" id="login-email" placeholder="email@example.com">
                        </label>
                        <label class="field">
                            <span>Password</span>
                            <input type="password" id="login-password" placeholder="Enter your password">
                        </label>
                        <button class="button" id="login-submit" type="submit">Log in</button>
                    </form>

                    <form id="register-form" class="auth-form is-hidden">
                        <div class="field-row">
                            <label class="field">
                                <span>Full name</span>
                                <input type="text" id="register-name" placeholder="Your full name">
                            </label>
                            <label class="field">
                                <span>Role</span>
                                <select id="register-role">
                                    <option value="farmer">Farmer</option>
                                    <option value="consultant">Consultant</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </label>
                        </div>

                        <div class="field-row">
                            <label class="field">
                                <span>Email</span>
                                <input type="email" id="register-email" placeholder="email@example.com">
                            </label>
                            <label class="field">
                                <span>Password</span>
                                <input type="password" id="register-password" placeholder="At least 6 characters">
                            </label>
                        </div>

                        <div id="register-farmer-fields">
                            <div class="field-row">
                                <label class="field">
                                    <span>Farm location</span>
                                    <input type="text" id="register-location" placeholder="Municipality, province, or sitio">
                                </label>
                                <label class="field">
                                    <span>Primary crop</span>
                                    <input type="text" id="register-primary-crop" placeholder="Rice, Corn, Tomato, etc.">
                                </label>
                            </div>
                        </div>

                        <div id="register-consultant-fields" class="is-hidden">
                            <div class="field-row">
                                <label class="field">
                                    <span>Specialty</span>
                                    <input type="text" id="register-specialty" placeholder="Pest Management, Soil Health, etc.">
                                </label>
                                <label class="field">
                                    <span>Bio</span>
                                    <input type="text" id="register-bio" placeholder="Short description of expertise">
                                </label>
                            </div>
                        </div>

                        <button class="button" id="register-submit" type="submit">Create account</button>
                    </form>
                </section>
            </div>
        </section>

        <section class="section" id="consultations">
            <div class="container">
                <div class="section-heading reveal">
                    <div>
                        <span class="eyebrow">Workspace</span>
                        <h2>Consultations</h2>
                    </div>
                    <p id="workspace-copy">
                        Create a case, open the thread, and manage the next action.
                    </p>
                </div>

                <div class="workspace-grid">
                    <form class="panel form-panel reveal" id="consultation-form">
                        <div class="panel-heading-row">
                            <div>
                                <span class="panel-kicker">New case</span>
                                <h3>Create consultation</h3>
                            </div>
                        </div>

                        <p class="field-hint" id="consultation-access-note">
                            Sign in as a farmer to create a consultation.
                        </p>

                        <label class="field">
                            <span>Concern title</span>
                            <input id="title-input" name="title" type="text" placeholder="Example: Rice leaves with spreading spots">
                        </label>

                        <div class="field-row">
                            <label class="field">
                                <span>Crop</span>
                                <input id="crop-input" name="crop" type="text" placeholder="Rice, Corn, Tomato, Pechay">
                            </label>
                            <label class="field">
                                <span>Urgency</span>
                                <select id="urgency-input" name="urgency">
                                    <option value="low">Low</option>
                                    <option value="medium" selected>Medium</option>
                                    <option value="high">High</option>
                                    <option value="critical">Critical</option>
                                </select>
                            </label>
                        </div>

                        <label class="field">
                            <span>Farm location</span>
                            <input id="location-input" name="location" type="text" placeholder="Municipality, province, or sitio">
                        </label>

                        <label class="field">
                            <span>Describe the issue</span>
                            <textarea id="concern-input" name="concern" rows="6" placeholder="Describe what you see in the field."></textarea>
                        </label>

                        <div class="form-footer">
                            <div class="button-row">
                                <button class="button" id="consultation-submit" type="submit">Submit concern</button>
                                <button type="button" class="button button-secondary" data-open-assistant>Ask AI first</button>
                            </div>
                        </div>
                    </form>

                    <section class="panel queue-panel reveal">
                        <div class="panel-heading-row">
                            <div>
                                <span class="panel-kicker">Queue</span>
                                <h3>Consultations</h3>
                            </div>
                            <span class="count-pill" id="consultation-count-badge">0</span>
                        </div>
                        <div class="field-hint" id="consultation-queue-note">
                            Log in to view role-specific consultations.
                        </div>
                        <div class="stack-list" id="consultation-list"></div>
                    </section>

                    <section class="panel thread-panel reveal">
                        <div class="thread-toolbar">
                            <div>
                                <span class="panel-kicker">Thread</span>
                                <h3 id="thread-title">Select a consultation</h3>
                            </div>
                            <button type="button" class="text-button" id="scroll-thread-bottom">Latest</button>
                        </div>

                        <div class="role-activity-banner" id="thread-role-banner">
                            Select a consultation.
                        </div>

                        <div class="admin-controls is-hidden" id="admin-controls">
                            <div class="field-row">
                                <label class="field">
                                    <span>Assign consultant</span>
                                    <select id="assign-consultant-select"></select>
                                </label>
                                <label class="field">
                                    <span>Update status</span>
                                    <select id="status-select">
                                        <option value="ai_triage">AI triage</option>
                                        <option value="expert_assigned">Expert assigned</option>
                                        <option value="monitoring">Monitoring</option>
                                        <option value="resolved">Resolved</option>
                                    </select>
                                </label>
                            </div>
                            <div class="button-row">
                                <button type="button" class="button button-secondary" id="assign-consultant-button">Save assignment</button>
                                <button type="button" class="button button-secondary" id="update-status-button">Save status</button>
                            </div>
                        </div>

                        <div class="thread-meta" id="thread-meta"></div>
                        <div class="message-stream" id="thread-messages"></div>
                        <form class="thread-form" id="thread-form">
                            <textarea id="thread-input" rows="3" placeholder="Send another update or response."></textarea>
                            <div class="button-row thread-form-actions">
                                <p class="field-hint" id="thread-role-hint">
                                    Log in to send a role-based message in this consultation.
                                </p>
                                <button class="button" id="thread-submit" type="submit">Send update</button>
                            </div>
                        </form>
                    </section>

                    <aside class="sidebar-column reveal">
                        <section class="panel sidebar-panel">
                            <div class="panel-heading-row">
                                <div>
                                    <span class="panel-kicker">Advisers</span>
                                    <h3>Available</h3>
                                </div>
                            </div>
                            <div class="sidebar-list" id="expert-list"></div>
                        </section>

                        <section class="panel sidebar-panel">
                            <div class="panel-heading-row">
                                <div>
                                    <span class="panel-kicker">Notifications</span>
                                    <h3>Updates</h3>
                                </div>
                            </div>
                            <div class="sidebar-list" id="notification-list"></div>
                        </section>

                        <section class="panel sidebar-panel">
                            <div class="panel-heading-row">
                                <div>
                                    <span class="panel-kicker">Admin</span>
                                    <h3>Users</h3>
                                </div>
                            </div>
                            <div class="sidebar-list" id="admin-user-list"></div>
                        </section>
                    </aside>
                </div>
            </div>
        </section>

    </main>

    <footer class="site-footer">
        <div class="container footer-row">
            <div>
                <p class="footer-brand">WeAgri</p>
                <p>Simple agricultural consultation workspace.</p>
            </div>
            <div class="footer-links">
                <a href="#access">Access</a>
                <a href="#consultations">Consultations</a>
            </div>
            <p class="footer-copy">&copy; <?= date('Y'); ?> WeAgri.</p>
        </div>
    </footer>

    <button type="button" class="assistant-fab" id="assistant-fab" data-open-assistant>
        Ask AI
    </button>

    <div class="assistant-shell" id="assistant-shell" aria-hidden="true">
        <button class="assistant-backdrop" type="button" data-close-assistant></button>
        <aside class="assistant-drawer" aria-label="AI assistant">
            <div class="assistant-header">
                <div>
                    <span class="panel-kicker">Assistant</span>
                    <h2>AI Help</h2>
                </div>
                <button type="button" class="icon-button" data-close-assistant>Close</button>
            </div>

            <div class="assistant-status">
                Ask a farming question, then start a consultation when needed.
            </div>

            <div class="assistant-messages" id="assistant-messages"></div>

            <form class="assistant-form" id="assistant-form">
                <textarea id="assistant-input" rows="3" placeholder="Ask about pests, crop symptoms, soil issues, fertilizer, or irrigation."></textarea>
                <div class="button-row assistant-actions">
                    <button class="button" id="assistant-submit" type="submit">Send</button>
                </div>
            </form>
        </aside>
    </div>

    <div class="toast-stack" id="toast-stack" aria-live="polite" aria-atomic="true"></div>

    <script>
        window.WEAGRI_INITIAL_STATE = <?= json_encode(
            $initialState,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
        ); ?>;
    </script>
    <script src="script.js?v=20260421-overview-functions"></script>
</body>
</html>
