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
    <title>WeAgri | Role-Based Consultation Platform</title>
    <meta
        name="description"
        content="WeAgri is a single-page application for farmers, consultants, and administrators with AgroLLM chat, consultation management, and role-based responses."
    >
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="page-decor decor-one"></div>
    <div class="page-decor decor-two"></div>
    <div class="page-decor decor-three"></div>

    <header class="site-header" id="home">
        <div class="container nav-row">
            <a class="brand" href="#home">
                <span class="brand-mark">WA</span>
                <span class="brand-copy">
                    <strong>WeAgri</strong>
                    <small>Farmers, consultants, and admins in one workspace</small>
                </span>
            </a>

            <button class="nav-toggle" id="nav-toggle" type="button" aria-expanded="false" aria-controls="site-nav">
                Menu
            </button>

            <nav class="site-nav" id="site-nav">
                <a href="#access">Access</a>
                <a href="#consultations">Consultations</a>
                <a href="#platform">Platform</a>
                <a href="#rag">AgroLLM + RAG</a>
                <button type="button" class="button button-secondary nav-cta" data-open-assistant>
                    Ask AgroLLM
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
                    <span class="eyebrow">WeAgri: role-based consultation for the farm sector</span>
                    <h1>One web app for farmers asking questions, consultants answering needs, and admins managing the flow.</h1>
                    <p class="hero-text">
                        Farmers can sign up and submit crop concerns, consultants can review and respond to field issues,
                        and administrators can oversee assignments and status updates. AgroLLM supports the first-response
                        workflow before more complex concerns move to human advisers.
                    </p>

                    <div class="hero-actions">
                        <a class="button" href="#access">Log in or Sign up</a>
                        <button type="button" class="button button-secondary" data-open-assistant>
                            Try the AI Assistant
                        </button>
                    </div>

                    <div class="hero-points">
                        <article class="mini-card">
                            <span>Farmers</span>
                            <strong>Create consultations, track updates, and follow up on field concerns.</strong>
                        </article>
                        <article class="mini-card">
                            <span>Consultants</span>
                            <strong>View consultation needs and send direct responses to farmers.</strong>
                        </article>
                        <article class="mini-card">
                            <span>Administrators</span>
                            <strong>Manage consultant assignments, statuses, and the overall support flow.</strong>
                        </article>
                    </div>
                </div>

                <aside class="hero-panel reveal">
                    <div class="panel-surface">
                        <div class="panel-heading-row">
                            <div>
                                <span class="panel-kicker">Operations signal</span>
                                <h2>Live consultation activity</h2>
                            </div>
                            <span class="source-pill" id="source-pill">Loading...</span>
                        </div>

                        <div class="signal-grid">
                            <article class="signal-card">
                                <span>Experts online</span>
                                <strong id="signal-online-experts">0</strong>
                            </article>
                            <article class="signal-card">
                                <span>Open consultations</span>
                                <strong id="signal-active-consultations">0</strong>
                            </article>
                            <article class="signal-card">
                                <span>Average response</span>
                                <strong id="signal-response-time">0m</strong>
                            </article>
                            <article class="signal-card">
                                <span>Unread alerts</span>
                                <strong id="signal-unread-alerts">0</strong>
                            </article>
                        </div>

                        <div class="live-thread-card">
                            <div class="live-thread-header">
                                <span class="dot"></span>
                                <span>Current consultation spotlight</span>
                            </div>
                            <h3 id="hero-thread-title">No active consultation yet</h3>
                            <div class="thread-chip-row">
                                <span class="meta-chip" id="hero-thread-status">Waiting for data</span>
                                <span class="meta-chip" id="hero-thread-expert">Expert availability pending</span>
                            </div>
                            <p id="hero-thread-preview">
                                Once the dashboard loads, this area will highlight the most recently updated consultation.
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

                    <div class="auth-profile-grid" id="auth-profile-grid">
                        <article class="signal-card">
                            <span>Name</span>
                            <strong id="auth-name">Guest user</strong>
                        </article>
                        <article class="signal-card">
                            <span>Email</span>
                            <strong id="auth-email">Not signed in</strong>
                        </article>
                        <article class="signal-card">
                            <span>Role details</span>
                            <strong id="auth-role-detail">Public access</strong>
                        </article>
                    </div>

                    <div class="role-capabilities">
                        <h3>What this account can do</h3>
                        <div class="stack-list" id="auth-capabilities"></div>
                    </div>

                    <div class="demo-account-box">
                        <h3>Demo accounts</h3>
                        <div class="stack-list">
                            <div class="mini-card compact-card">Admin: <strong>`admin@weagri.local` / `admin123`</strong></div>
                            <div class="mini-card compact-card">Farmer: <strong>`farmer@weagri.local` / `farmer123`</strong></div>
                            <div class="mini-card compact-card">Consultant: <strong>`liza@weagri.local` / `consultant123`</strong></div>
                        </div>
                    </div>

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

                        <p class="field-hint">
                            For school-project demos this sign-up allows all roles. In production, admin creation should be restricted.
                        </p>
                        <button class="button" id="register-submit" type="submit">Create account</button>
                    </form>
                </section>
            </div>
        </section>

        <section class="metrics-strip">
            <div class="container metrics-grid">
                <article class="metric-card">
                    <span class="metric-label">Objective</span>
                    <strong class="metric-value">Real-time support</strong>
                    <p>Farmers receive AI triage first, then consultant support for more complex issues.</p>
                </article>
                <article class="metric-card">
                    <span class="metric-label">Role-based flow</span>
                    <strong class="metric-value">3 account types</strong>
                    <p>Farmer, consultant, and admin dashboards are driven from the same SPA.</p>
                </article>
                <article class="metric-card">
                    <span class="metric-label">Modeling approach</span>
                    <strong class="metric-value">RAG-powered</strong>
                    <p>AgroLLM answers are grounded in a curated agricultural knowledge base.</p>
                </article>
                <article class="metric-card">
                    <span class="metric-label">Deployment fit</span>
                    <strong class="metric-value">XAMPP-ready</strong>
                    <p>Built with HTML, CSS, JavaScript, PHP, and MySQL for straightforward local deployment.</p>
                </article>
            </div>
        </section>

        <section class="section" id="consultations">
            <div class="container">
                <div class="section-heading reveal">
                    <div>
                        <span class="eyebrow">Consultation workspace</span>
                        <h2>Role-based handling from farmer concern to consultant response.</h2>
                    </div>
                    <p id="workspace-copy">
                        Farmers create concerns, consultants reply to needs, and admins manage assignments and case status.
                    </p>
                </div>

                <div class="workspace-grid">
                    <form class="panel form-panel reveal" id="consultation-form">
                        <div class="panel-heading-row">
                            <div>
                                <span class="panel-kicker">New concern</span>
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
                            <textarea id="concern-input" name="concern" rows="6" placeholder="Describe crop symptoms, recent weather, fertilizer changes, pest signs, or anything unusual in the field."></textarea>
                        </label>

                        <div class="form-footer">
                            <p class="field-hint">AgroLLM answers first. Consultant assignment happens when the concern needs a human response.</p>
                            <div class="button-row">
                                <button class="button" id="consultation-submit" type="submit">Submit concern</button>
                                <button type="button" class="button button-secondary" data-open-assistant>Ask AgroLLM first</button>
                            </div>
                        </div>
                    </form>

                    <section class="panel queue-panel reveal">
                        <div class="panel-heading-row">
                            <div>
                                <span class="panel-kicker">Live queue</span>
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
                                <span class="panel-kicker">Live thread</span>
                                <h3 id="thread-title">Select a consultation</h3>
                            </div>
                            <button type="button" class="text-button" id="scroll-thread-bottom">Latest update</button>
                        </div>

                        <div class="role-activity-banner" id="thread-role-banner">
                            Thread actions will change based on whether you are a farmer, consultant, or admin.
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
                                    <span class="panel-kicker">Consultant availability</span>
                                    <h3>Advisers on deck</h3>
                                </div>
                            </div>
                            <div class="sidebar-list" id="expert-list"></div>
                        </section>

                        <section class="panel sidebar-panel">
                            <div class="panel-heading-row">
                                <div>
                                    <span class="panel-kicker">Notifications</span>
                                    <h3>Role-based updates</h3>
                                </div>
                            </div>
                            <div class="sidebar-list" id="notification-list"></div>
                        </section>

                        <section class="panel sidebar-panel">
                            <div class="panel-heading-row">
                                <div>
                                    <span class="panel-kicker">Admin overview</span>
                                    <h3>Registered users</h3>
                                </div>
                            </div>
                            <div class="sidebar-list" id="admin-user-list"></div>
                        </section>
                    </aside>
                </div>
            </div>
        </section>

        <section class="section section-soft" id="platform">
            <div class="container">
                <div class="section-heading reveal">
                    <div>
                        <span class="eyebrow">Platform foundation</span>
                        <h2>Built around the original WeAgri objectives and expected outcomes.</h2>
                    </div>
                    <p>
                        This version adds secure role-based access for farmer accounts, consultant response workflows,
                        and admin-level management while keeping the AI and RAG foundation in place.
                    </p>
                </div>

                <div class="feature-grid">
                    <article class="feature-card reveal">
                        <span class="feature-tag">Access</span>
                        <h3>Farmer sign-up and log-in</h3>
                        <p>Farmers can create accounts, sign in, submit consultations, and track their own conversation history.</p>
                    </article>
                    <article class="feature-card reveal">
                        <span class="feature-tag">Access</span>
                        <h3>Consultant response workspace</h3>
                        <p>Consultants can log in, view the consultation queue, and send direct responses to farmer concerns.</p>
                    </article>
                    <article class="feature-card reveal">
                        <span class="feature-tag">Access</span>
                        <h3>Admin management controls</h3>
                        <p>Admins can assign consultants, change statuses, and monitor registered accounts and response flow.</p>
                    </article>
                    <article class="feature-card reveal">
                        <span class="feature-tag">Outcome</span>
                        <h3>Improved access to agricultural advice</h3>
                        <p>Farmers can ask about crops, pests, soil management, and farming techniques without needing travel-based consultation.</p>
                    </article>
                    <article class="feature-card reveal">
                        <span class="feature-tag">Outcome</span>
                        <h3>Faster problem solving</h3>
                        <p>AgroLLM handles common concerns quickly while consultant accounts cover more complex cases.</p>
                    </article>
                    <article class="feature-card reveal">
                        <span class="feature-tag">Outcome</span>
                        <h3>Better farming decisions</h3>
                        <p>Accurate, timely, and role-routed information supports stronger field decisions and productivity improvements.</p>
                    </article>
                </div>
            </div>
        </section>

        <section class="section" id="rag">
            <div class="container knowledge-layout">
                <div class="knowledge-copy reveal">
                    <span class="eyebrow">AgroLLM + RAG modeling</span>
                    <h2>Reliable guidance starts with context-grounded agricultural knowledge.</h2>
                    <p>
                        The AI layer retrieves relevant farming references before composing a response. This supports better
                        first-response guidance and helps the system decide when a consultant should take over the case.
                    </p>

                    <ul class="bullet-list">
                        <li>Supports immediate answers for common farming concerns.</li>
                        <li>Creates a better handoff between AI triage and human consultant response.</li>
                        <li>Keeps WeAgri aligned with a context-based agricultural information system.</li>
                    </ul>
                </div>

                <div class="knowledge-grid" id="knowledge-list"></div>
            </div>
        </section>
    </main>

    <footer class="site-footer">
        <div class="container footer-row">
            <div>
                <p class="footer-brand">WeAgri</p>
                <p>Single-page agricultural consultation services with role-based access control.</p>
            </div>
            <div class="footer-links">
                <a href="#access">Access</a>
                <a href="#consultations">Consultations</a>
                <a href="#rag">AgroLLM + RAG</a>
            </div>
            <p class="footer-copy">© <?= date('Y'); ?> WeAgri. Built with PHP, MySQL, and plain web technologies.</p>
        </div>
    </footer>

    <button type="button" class="assistant-fab" id="assistant-fab" data-open-assistant>
        AgroLLM Assistant
    </button>

    <div class="assistant-shell" id="assistant-shell" aria-hidden="true">
        <button class="assistant-backdrop" type="button" data-close-assistant></button>
        <aside class="assistant-drawer" aria-label="AgroLLM assistant">
            <div class="assistant-header">
                <div>
                    <span class="panel-kicker">AI chat assistant</span>
                    <h2>AgroLLM</h2>
                </div>
                <button type="button" class="icon-button" data-close-assistant>Close</button>
            </div>

            <div class="assistant-status">
                Instant AI guidance for common farming concerns. Complex cases can be turned into full consultations.
            </div>

            <div class="assistant-messages" id="assistant-messages"></div>

            <form class="assistant-form" id="assistant-form">
                <textarea id="assistant-input" rows="3" placeholder="Ask about pests, crop symptoms, soil issues, fertilizer, or irrigation."></textarea>
                <div class="button-row assistant-actions">
                    <p class="field-hint">The answer uses the same agricultural knowledge layer shown on this page.</p>
                    <button class="button" id="assistant-submit" type="submit">Send to AgroLLM</button>
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
    <script src="script.js"></script>
</body>
</html>
