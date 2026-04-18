const state = {
  source: "",
  sourceLabel: "",
  auth: {
    authenticated: false,
    role: "guest",
    roleLabel: "Guest",
    user: null,
  },
  permissions: {},
  stats: {
    onlineExperts: 0,
    activeConsultations: 0,
    averageResponseMinutes: 0,
    unreadNotifications: 0,
  },
  consultations: [],
  experts: [],
  consultantOptions: [],
  notifications: [],
  knowledgeHighlights: [],
  admin: null,
  activeConsultationId: null,
  activeAuthView: "login",
  assistantMessages: [
    {
      id: `assistant-${Date.now()}`,
      sender: "ai",
      text:
        "Hello, I am AgroLLM. Ask me about pests, crop symptoms, fertilizer issues, soil management, or irrigation concerns, and I will help with a first response.",
      meta: null,
      time: new Date().toISOString(),
    },
  ],
  pollHandle: null,
};

const dom = {
  navToggle: document.getElementById("nav-toggle"),
  siteNav: document.getElementById("site-nav"),
  sourcePill: document.getElementById("source-pill"),
  navUserChip: document.getElementById("nav-user-chip"),
  navLogoutButton: document.getElementById("nav-logout-button"),
  signalOnlineExperts: document.getElementById("signal-online-experts"),
  signalActiveConsultations: document.getElementById("signal-active-consultations"),
  signalResponseTime: document.getElementById("signal-response-time"),
  signalUnreadAlerts: document.getElementById("signal-unread-alerts"),
  heroThreadTitle: document.getElementById("hero-thread-title"),
  heroThreadStatus: document.getElementById("hero-thread-status"),
  heroThreadExpert: document.getElementById("hero-thread-expert"),
  heroThreadPreview: document.getElementById("hero-thread-preview"),
  authSummaryTitle: document.getElementById("auth-summary-title"),
  authSummaryCopy: document.getElementById("auth-summary-copy"),
  authRoleBadge: document.getElementById("auth-role-badge"),
  authName: document.getElementById("auth-name"),
  authEmail: document.getElementById("auth-email"),
  authRoleDetail: document.getElementById("auth-role-detail"),
  authCapabilities: document.getElementById("auth-capabilities"),
  logoutButton: document.getElementById("logout-button"),
  showLogin: document.getElementById("show-login"),
  showRegister: document.getElementById("show-register"),
  loginForm: document.getElementById("login-form"),
  registerForm: document.getElementById("register-form"),
  loginEmail: document.getElementById("login-email"),
  loginPassword: document.getElementById("login-password"),
  loginSubmit: document.getElementById("login-submit"),
  registerName: document.getElementById("register-name"),
  registerRole: document.getElementById("register-role"),
  registerEmail: document.getElementById("register-email"),
  registerPassword: document.getElementById("register-password"),
  registerLocation: document.getElementById("register-location"),
  registerPrimaryCrop: document.getElementById("register-primary-crop"),
  registerSpecialty: document.getElementById("register-specialty"),
  registerBio: document.getElementById("register-bio"),
  registerFarmerFields: document.getElementById("register-farmer-fields"),
  registerConsultantFields: document.getElementById("register-consultant-fields"),
  registerSubmit: document.getElementById("register-submit"),
  consultationForm: document.getElementById("consultation-form"),
  consultationAccessNote: document.getElementById("consultation-access-note"),
  consultationQueueNote: document.getElementById("consultation-queue-note"),
  titleInput: document.getElementById("title-input"),
  cropInput: document.getElementById("crop-input"),
  urgencyInput: document.getElementById("urgency-input"),
  locationInput: document.getElementById("location-input"),
  concernInput: document.getElementById("concern-input"),
  consultationSubmit: document.getElementById("consultation-submit"),
  consultationCountBadge: document.getElementById("consultation-count-badge"),
  consultationList: document.getElementById("consultation-list"),
  threadTitle: document.getElementById("thread-title"),
  threadRoleBanner: document.getElementById("thread-role-banner"),
  adminControls: document.getElementById("admin-controls"),
  assignConsultantSelect: document.getElementById("assign-consultant-select"),
  assignConsultantButton: document.getElementById("assign-consultant-button"),
  statusSelect: document.getElementById("status-select"),
  updateStatusButton: document.getElementById("update-status-button"),
  threadMeta: document.getElementById("thread-meta"),
  threadMessages: document.getElementById("thread-messages"),
  threadForm: document.getElementById("thread-form"),
  threadInput: document.getElementById("thread-input"),
  threadRoleHint: document.getElementById("thread-role-hint"),
  threadSubmit: document.getElementById("thread-submit"),
  scrollThreadBottom: document.getElementById("scroll-thread-bottom"),
  expertList: document.getElementById("expert-list"),
  notificationList: document.getElementById("notification-list"),
  adminUserList: document.getElementById("admin-user-list"),
  knowledgeList: document.getElementById("knowledge-list"),
  assistantShell: document.getElementById("assistant-shell"),
  assistantFab: document.getElementById("assistant-fab"),
  assistantMessages: document.getElementById("assistant-messages"),
  assistantForm: document.getElementById("assistant-form"),
  assistantInput: document.getElementById("assistant-input"),
  assistantSubmit: document.getElementById("assistant-submit"),
  toastStack: document.getElementById("toast-stack"),
};

document.addEventListener("DOMContentLoaded", () => {
  bindEvents();
  setupRevealObserver();
  switchAuthView(state.activeAuthView);
  syncRegisterRoleFields();
  applyBootstrap(window.WEAGRI_INITIAL_STATE || null);
  refreshState();
  state.pollHandle = window.setInterval(refreshState, 12000);
});

function bindEvents() {
  dom.navToggle?.addEventListener("click", () => {
    const isOpen = dom.siteNav.classList.toggle("is-open");
    dom.navToggle.setAttribute("aria-expanded", String(isOpen));
  });

  document.querySelectorAll("#site-nav a").forEach((link) => {
    link.addEventListener("click", () => {
      dom.siteNav.classList.remove("is-open");
      dom.navToggle?.setAttribute("aria-expanded", "false");
    });
  });

  document.querySelectorAll("[data-open-assistant]").forEach((button) => {
    button.addEventListener("click", openAssistant);
  });

  document.querySelectorAll("[data-close-assistant]").forEach((button) => {
    button.addEventListener("click", closeAssistant);
  });

  dom.showLogin?.addEventListener("click", () => switchAuthView("login"));
  dom.showRegister?.addEventListener("click", () => switchAuthView("register"));
  dom.registerRole?.addEventListener("change", syncRegisterRoleFields);

  dom.loginForm?.addEventListener("submit", submitLogin);
  dom.registerForm?.addEventListener("submit", submitRegister);
  dom.logoutButton?.addEventListener("click", submitLogout);
  dom.navLogoutButton?.addEventListener("click", submitLogout);

  dom.consultationForm?.addEventListener("submit", submitConsultation);
  dom.threadForm?.addEventListener("submit", submitThreadMessage);
  dom.assignConsultantButton?.addEventListener("click", submitAssignment);
  dom.updateStatusButton?.addEventListener("click", submitStatusUpdate);
  dom.assistantForm?.addEventListener("submit", submitAssistantMessage);
  dom.scrollThreadBottom?.addEventListener("click", () => scrollMessagesToBottom(dom.threadMessages));

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape") {
      closeAssistant();
    }
  });
}

function setupRevealObserver() {
  const elements = document.querySelectorAll(".reveal");
  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add("is-visible");
          observer.unobserve(entry.target);
        }
      });
    },
    { threshold: 0.15, rootMargin: "0px 0px -40px 0px" }
  );

  elements.forEach((element) => observer.observe(element));
}

async function refreshState() {
  try {
    const response = await fetchJson("api/bootstrap.php");
    if (response.ok && response.state) {
      applyBootstrap(response.state);
    }
  } catch (error) {
    console.error(error);
  }
}

function applyBootstrap(payload) {
  if (!payload) {
    return;
  }

  const previousSelection = state.activeConsultationId;

  state.source = payload.source || "";
  state.sourceLabel = payload.source_label || "";
  state.auth = {
    authenticated: Boolean(payload.auth?.authenticated),
    role: payload.auth?.role || "guest",
    roleLabel: payload.auth?.role_label || "Guest",
    user: payload.auth?.user || null,
  };
  state.permissions = payload.permissions || {};
  state.stats = {
    onlineExperts: payload.stats?.online_experts ?? 0,
    activeConsultations: payload.stats?.active_consultations ?? 0,
    averageResponseMinutes: payload.stats?.average_response_minutes ?? 0,
    unreadNotifications: payload.stats?.unread_notifications ?? 0,
  };
  state.consultations = Array.isArray(payload.consultations) ? payload.consultations : [];
  state.experts = Array.isArray(payload.experts) ? payload.experts : [];
  state.consultantOptions = Array.isArray(payload.consultant_options) ? payload.consultant_options : [];
  state.notifications = Array.isArray(payload.notifications) ? payload.notifications : [];
  state.knowledgeHighlights = Array.isArray(payload.knowledge_highlights) ? payload.knowledge_highlights : [];
  state.admin = payload.admin || null;

  if (
    previousSelection &&
    state.consultations.some((consultation) => consultation.id === previousSelection)
  ) {
    state.activeConsultationId = previousSelection;
  } else {
    state.activeConsultationId = state.consultations[0]?.id ?? null;
  }

  renderDashboard();
}

function renderDashboard() {
  renderSignals();
  renderAuthSummary();
  renderConsultationAccess();
  renderConsultationList();
  renderThread();
  renderExperts();
  renderNotifications();
  renderAdminOverview();
  renderKnowledge();
  renderAssistantMessages();
}

function renderSignals() {
  dom.sourcePill.textContent = state.sourceLabel || "Loading...";
  dom.signalOnlineExperts.textContent = String(state.stats.onlineExperts);
  dom.signalActiveConsultations.textContent = String(state.stats.activeConsultations);
  dom.signalResponseTime.textContent = `${state.stats.averageResponseMinutes || 0}m`;
  dom.signalUnreadAlerts.textContent = String(state.stats.unreadNotifications);
  dom.navUserChip.textContent = state.auth.authenticated
    ? `${state.auth.user?.full_name || "User"} (${state.auth.roleLabel})`
    : "Guest";
  dom.navLogoutButton.classList.toggle("is-hidden", !state.auth.authenticated);

  const spotlight = state.consultations[0];
  if (!spotlight) {
    dom.heroThreadTitle.textContent = state.auth.authenticated
      ? "No consultation available yet"
      : "Sign in to view consultations";
    dom.heroThreadStatus.textContent = state.auth.authenticated ? "Waiting" : "Guest view";
    dom.heroThreadExpert.textContent = "No expert assigned";
    dom.heroThreadPreview.textContent = state.auth.authenticated
      ? "Create or open a consultation to start the real-time support flow."
      : "Consultation details stay private until you log in.";
    return;
  }

  dom.heroThreadTitle.textContent = spotlight.title;
  dom.heroThreadStatus.textContent = spotlight.status_label || spotlight.status;
  dom.heroThreadExpert.textContent = spotlight.assigned_expert_name
    ? `Expert: ${spotlight.assigned_expert_name}`
    : "AI triage active";
  dom.heroThreadPreview.textContent = spotlight.last_message_preview || spotlight.summary || "";
}

function renderAuthSummary() {
  const user = state.auth.user;
  const capabilityMap = {
    guest: [
      "Browse the public project information and AgroLLM knowledge highlights.",
      "Use the AI assistant for quick questions before creating an account.",
      "Log in or sign up to access private consultations.",
    ],
    farmer: [
      "Create new consultations and track only your own concerns.",
      "Send follow-up messages that trigger fresh AI guidance and consultant review.",
      "Receive consultation-specific notifications and updates.",
    ],
    consultant: [
      "View the consultation queue and read farmer concerns.",
      "Respond directly to farmer needs as a consultant.",
      "Take ownership of an unassigned consultation when sending a response.",
    ],
    admin: [
      "View all consultations across the platform.",
      "Assign consultants to consultations and update case status.",
      "Monitor registered users and the response workflow.",
    ],
  };

  dom.authRoleBadge.textContent = state.auth.roleLabel;

  if (!state.auth.authenticated || !user) {
    dom.authSummaryTitle.textContent = "Continue as guest";
    dom.authSummaryCopy.textContent =
      "Log in to create consultations, respond as a consultant, or manage assignments as an administrator.";
    dom.authName.textContent = "Guest user";
    dom.authEmail.textContent = "Not signed in";
    dom.authRoleDetail.textContent = "Public access only";
    dom.logoutButton.classList.add("is-hidden");
  } else {
    dom.authSummaryTitle.textContent = `Signed in as ${state.auth.roleLabel}`;
    dom.authSummaryCopy.textContent =
      state.auth.role === "farmer"
        ? "You can now submit agricultural concerns, follow up on your cases, and monitor responses."
        : state.auth.role === "consultant"
          ? "You can now view the consultation queue and send responses to farmer needs."
          : "You can now manage consultant assignments, statuses, and registered users.";
    dom.authName.textContent = user.full_name || "User";
    dom.authEmail.textContent = user.email || "";
    dom.authRoleDetail.textContent =
      state.auth.role === "farmer"
        ? `${user.location || "Farm location"} | ${user.primary_crop || "General farming"}`
        : state.auth.role === "consultant"
          ? `${user.specialty || "General Agronomy"}`
          : "Platform-wide oversight";
    dom.logoutButton.classList.remove("is-hidden");
  }

  dom.authCapabilities.innerHTML = "";
  const capabilities = capabilityMap[state.auth.role] || capabilityMap.guest;
  capabilities.forEach((item) => {
    const card = document.createElement("div");
    card.className = "mini-card compact-card";
    card.textContent = item;
    dom.authCapabilities.appendChild(card);
  });
}

function renderConsultationAccess() {
  const canCreate = Boolean(state.permissions.can_create_consultation);
  const role = state.auth.role;

  const inputElements = [
    dom.titleInput,
    dom.cropInput,
    dom.urgencyInput,
    dom.locationInput,
    dom.concernInput,
    dom.consultationSubmit,
  ];

  inputElements.forEach((element) => {
    element.disabled = !canCreate;
  });

  dom.consultationAccessNote.textContent = canCreate
    ? "Your farmer account can create a new consultation for crop, pest, soil, or farm-practice concerns."
    : role === "consultant"
      ? "Consultant accounts cannot create consultations, but they can respond to farmer needs in the thread."
      : role === "admin"
        ? "Admin accounts manage assignments and statuses rather than creating farmer consultations."
        : "Sign in as a farmer to create a consultation.";

  dom.consultationQueueNote.textContent = !state.auth.authenticated
    ? "Log in to view role-specific consultations."
    : role === "farmer"
      ? "You are viewing only your own consultations."
      : role === "consultant"
        ? "You can view the consultation queue and respond as a consultant."
        : "You can view and manage all consultations.";
}

function renderConsultationList() {
  dom.consultationCountBadge.textContent = String(state.consultations.length);
  dom.consultationList.innerHTML = "";

  if (state.consultations.length === 0) {
    dom.consultationList.appendChild(
      buildEmptyState(
        state.auth.authenticated
          ? "No consultations are available for this account yet."
          : "Sign in to view consultations."
      )
    );
    return;
  }

  state.consultations.forEach((consultation) => {
    const button = document.createElement("button");
    button.type = "button";
    button.className = "consultation-item";
    if (consultation.id === state.activeConsultationId) {
      button.classList.add("is-active");
    }

    button.innerHTML = `
      <div class="consultation-item-header">
        <span class="message-author">${escapeHtml(consultation.crop || "Crop")}</span>
        <span class="consultation-meta">${formatDate(consultation.updated_at)}</span>
      </div>
      <h4>${escapeHtml(consultation.title)}</h4>
      <p>${escapeHtml(consultation.last_message_preview || consultation.summary || "")}</p>
      <div class="pill-row">
        <span class="status-pill">${escapeHtml(consultation.status_label || consultation.status)}</span>
        <span class="urgency-pill">${escapeHtml(capitalize(consultation.urgency || "medium"))}</span>
        <span class="topic-pill">${escapeHtml(consultation.category || "General Advisory")}</span>
      </div>
    `;

    button.addEventListener("click", () => {
      state.activeConsultationId = consultation.id;
      renderConsultationList();
      renderThread();
    });

    dom.consultationList.appendChild(button);
  });
}

function renderThread() {
  const consultation = getActiveConsultation();
  const role = state.auth.role;
  dom.threadMessages.innerHTML = "";
  dom.threadMeta.innerHTML = "";

  dom.adminControls.classList.toggle("is-hidden", !(role === "admin" && consultation));
  dom.threadForm.classList.remove("is-disabled");

  if (!consultation) {
    dom.threadTitle.textContent = "Select a consultation";
    dom.threadRoleBanner.textContent = state.auth.authenticated
      ? "Choose a consultation from the queue to view messages and available actions."
      : "Sign in first, then choose a consultation to continue.";
    dom.threadMessages.appendChild(
      buildEmptyState("Choose an existing consultation from the queue or log in to access private consultations.")
    );
    setThreadFormEnabled(false);
    return;
  }

  dom.threadTitle.textContent = consultation.title;
  dom.threadRoleBanner.textContent =
    role === "farmer"
      ? "Farmer mode: send follow-up field updates and questions in this thread."
      : role === "consultant"
        ? "Consultant mode: send a response that directly addresses the farmer's need."
        : role === "admin"
          ? "Admin mode: assign a consultant or update the consultation status below."
          : "Guest mode: thread actions are disabled until you log in.";

  const metaItems = [
    consultation.status_label || consultation.status,
    consultation.urgency ? `${capitalize(consultation.urgency)} urgency` : "",
    consultation.location || "",
    consultation.assigned_expert_name ? `Assigned to ${consultation.assigned_expert_name}` : "No consultant assigned yet",
    consultation.farmer_name ? `Farmer: ${consultation.farmer_name}` : "",
  ].filter(Boolean);

  metaItems.forEach((item) => {
    const chip = document.createElement("span");
    chip.className = "meta-chip";
    chip.textContent = item;
    dom.threadMeta.appendChild(chip);
  });

  if (role === "admin") {
    populateAdminControls(consultation);
  }

  const messages = Array.isArray(consultation.messages) ? consultation.messages : [];
  if (messages.length === 0) {
    dom.threadMessages.appendChild(buildEmptyState("No messages in this thread yet."));
  } else {
    messages.forEach((message) => {
      const card = document.createElement("article");
      card.className = "message-card";
      card.dataset.sender = message.sender_type || "ai";

      const references = Array.isArray(message.references) && message.references.length
        ? `<div class="pill-row">${message.references
            .map((reference) => `<span class="reference-pill">${escapeHtml(reference)}</span>`)
            .join("")}</div>`
        : "";

      card.innerHTML = `
        <div class="message-top">
          <span class="message-author">${escapeHtml(message.sender_name || "WeAgri")}</span>
          <span class="message-time">${formatDate(message.created_at)}</span>
        </div>
        <p>${escapeHtml(message.message || "")}</p>
        ${references}
      `;

      dom.threadMessages.appendChild(card);
    });
  }

  if (role === "farmer") {
    setThreadFormEnabled(true);
    dom.threadInput.placeholder = "Share a new field observation or follow-up question.";
    dom.threadRoleHint.textContent = "Farmer mode: each message can trigger fresh AI guidance and consultant review.";
    dom.threadSubmit.textContent = "Send follow-up";
  } else if (role === "consultant") {
    setThreadFormEnabled(true);
    dom.threadInput.placeholder = "Write a consultant response for the farmer.";
    dom.threadRoleHint.textContent =
      consultation.assigned_expert_id && consultation.assigned_expert_name !== state.auth.user?.full_name
        ? "If another consultant is already assigned, only that consultant should continue the response flow."
        : "Consultant mode: your reply is sent directly to the farmer and can claim the consultation if unassigned.";
    dom.threadSubmit.textContent = "Send consultant response";
  } else {
    setThreadFormEnabled(false);
    dom.threadInput.placeholder = "Thread messaging is disabled for this account role.";
    dom.threadRoleHint.textContent =
      role === "admin"
        ? "Admin mode: use the controls above to manage assignment and status."
        : "Log in as a farmer or consultant to send thread messages.";
    dom.threadSubmit.textContent = "Send update";
  }

  scrollMessagesToBottom(dom.threadMessages);
}

function populateAdminControls(consultation) {
  dom.assignConsultantSelect.innerHTML = "";

  const defaultOption = document.createElement("option");
  defaultOption.value = "";
  defaultOption.textContent = "Choose consultant";
  dom.assignConsultantSelect.appendChild(defaultOption);

  state.consultantOptions.forEach((consultant) => {
    const option = document.createElement("option");
    option.value = consultant.id;
    option.textContent = `${consultant.full_name} - ${consultant.specialty}`;
    if (consultation.assigned_expert_id === consultant.id) {
      option.selected = true;
    }
    dom.assignConsultantSelect.appendChild(option);
  });

  dom.statusSelect.value = consultation.status || "monitoring";
}

function renderExperts() {
  dom.expertList.innerHTML = "";

  if (!state.experts.length) {
    dom.expertList.appendChild(buildEmptyState("No consultant status available yet."));
    return;
  }

  state.experts.forEach((expert) => {
    const card = document.createElement("article");
    card.className = "expert-card";
    card.innerHTML = `
      <div class="expert-top">
        <div>
          <span class="message-author">${escapeHtml(expert.specialty)}</span>
          <h4>${escapeHtml(expert.full_name)}</h4>
        </div>
        <span class="expert-status" data-status="${escapeHtml(expert.status)}">${escapeHtml(capitalize(expert.status))}</span>
      </div>
      <p>${escapeHtml(expert.bio || "")}</p>
      <div class="pill-row">
        <span class="topic-pill">Avg response ${escapeHtml(String(expert.response_minutes))} min</span>
      </div>
    `;
    dom.expertList.appendChild(card);
  });
}

function renderNotifications() {
  dom.notificationList.innerHTML = "";

  if (!state.notifications.length) {
    dom.notificationList.appendChild(
      buildEmptyState(
        state.auth.authenticated
          ? "No notifications for this account yet."
          : "Log in to receive consultation-specific notifications."
      )
    );
    return;
  }

  state.notifications.forEach((notification) => {
    const button = document.createElement("button");
    button.type = "button";
    button.className = `notification-card ${notification.is_read ? "is-read" : "unread"}`;
    button.innerHTML = `
      <div class="notification-top">
        <span class="notification-type">${escapeHtml(notification.type || "Update")}</span>
        <span class="notification-time">${formatDate(notification.created_at)}</span>
      </div>
      <h4>${escapeHtml(notification.title || "Notification")}</h4>
      <p>${escapeHtml(notification.body || "")}</p>
    `;

    button.addEventListener("click", async () => {
      if (state.auth.authenticated && !notification.is_read) {
        await markNotificationRead(notification.id);
      }

      if (notification.consultation_id) {
        state.activeConsultationId = notification.consultation_id;
        renderConsultationList();
        renderThread();
        document.getElementById("consultations")?.scrollIntoView({ behavior: "smooth", block: "start" });
      }
    });

    dom.notificationList.appendChild(button);
  });
}

function renderAdminOverview() {
  dom.adminUserList.innerHTML = "";

  if (!state.auth.authenticated || state.auth.role !== "admin" || !state.admin) {
    dom.adminUserList.appendChild(buildEmptyState("Admin account access is required to view registered users."));
    return;
  }

  const counts = state.admin.user_counts || {};
  const summary = document.createElement("article");
  summary.className = "expert-card";
  summary.innerHTML = `
    <div class="pill-row">
      <span class="topic-pill">Admins ${escapeHtml(String(counts.admin || 0))}</span>
      <span class="topic-pill">Farmers ${escapeHtml(String(counts.farmer || 0))}</span>
      <span class="topic-pill">Consultants ${escapeHtml(String(counts.consultant || 0))}</span>
    </div>
  `;
  dom.adminUserList.appendChild(summary);

  const users = Array.isArray(state.admin.users) ? state.admin.users : [];
  users.forEach((user) => {
    const card = document.createElement("article");
    card.className = "notification-card";
    card.innerHTML = `
      <div class="notification-top">
        <span class="notification-type">${escapeHtml(user.role_label || user.role)}</span>
      </div>
      <h4>${escapeHtml(user.full_name)}</h4>
      <p>${escapeHtml(user.email)}</p>
    `;
    dom.adminUserList.appendChild(card);
  });
}

function renderKnowledge() {
  dom.knowledgeList.innerHTML = "";

  if (!state.knowledgeHighlights.length) {
    dom.knowledgeList.appendChild(buildEmptyState("Knowledge highlights will appear here after loading."));
    return;
  }

  state.knowledgeHighlights.forEach((entry) => {
    const article = document.createElement("article");
    article.className = "knowledge-card reveal is-visible";
    const recommendations = Array.isArray(entry.recommendations)
      ? entry.recommendations.map((line) => `<li>${escapeHtml(line)}</li>`).join("")
      : "";

    article.innerHTML = `
      <span class="feature-tag">${escapeHtml(entry.topic || "Knowledge base")}</span>
      <h3>${escapeHtml(entry.title || "")}</h3>
      <p>${escapeHtml(entry.excerpt || "")}</p>
      <ul class="bullet-list">${recommendations}</ul>
      <div class="pill-row">
        <span class="topic-pill">${escapeHtml(entry.source || "Reference")}</span>
      </div>
    `;

    dom.knowledgeList.appendChild(article);
  });
}

function renderAssistantMessages() {
  dom.assistantMessages.innerHTML = "";

  state.assistantMessages.forEach((message) => {
    const article = document.createElement("article");
    article.className = "assistant-message";
    article.dataset.sender = message.sender;

    const metaActions =
      message.sender === "ai" && message.meta?.escalate_to_expert
        ? `
          <div class="assistant-actions-row">
            <button type="button" class="assistant-inline-action" data-prefill-consultation="${escapeAttribute(
              JSON.stringify({
                title: message.meta.suggested_title || "",
                crop: message.meta.crop || "",
                concern: message.meta.original_question || "",
              })
            )}">
              Create consultation from this answer
            </button>
          </div>
        `
        : "";

    const references = Array.isArray(message.meta?.references) && message.meta.references.length
      ? `<div class="pill-row">${message.meta.references
          .map((reference) => `<span class="reference-pill">${escapeHtml(reference)}</span>`)
          .join("")}</div>`
      : "";

    article.innerHTML = `
      <div class="assistant-meta">
        <span class="message-author">${message.sender === "user" ? "User" : "AgroLLM"}</span>
        <span class="message-time">${message.time ? formatDate(message.time) : "Just now"}</span>
      </div>
      <p>${escapeHtml(message.text)}</p>
      ${references}
      ${metaActions}
    `;

    dom.assistantMessages.appendChild(article);
  });

  dom.assistantMessages.querySelectorAll("[data-prefill-consultation]").forEach((button) => {
    button.addEventListener("click", () => {
      const payload = JSON.parse(button.dataset.prefillConsultation || "{}");
      prefillConsultationFromAssistant(payload);
      closeAssistant();
    });
  });

  scrollMessagesToBottom(dom.assistantMessages);
}

function switchAuthView(view) {
  state.activeAuthView = view;
  const loginVisible = view === "login";
  dom.loginForm.classList.toggle("is-hidden", !loginVisible);
  dom.registerForm.classList.toggle("is-hidden", loginVisible);
  dom.showLogin.classList.toggle("is-active", loginVisible);
  dom.showRegister.classList.toggle("is-active", !loginVisible);
}

function syncRegisterRoleFields() {
  const role = dom.registerRole.value;
  dom.registerFarmerFields.classList.toggle("is-hidden", role !== "farmer");
  dom.registerConsultantFields.classList.toggle("is-hidden", role !== "consultant");
}

async function submitLogin(event) {
  event.preventDefault();

  const email = dom.loginEmail.value.trim();
  const password = dom.loginPassword.value;
  if (!email || !password) {
    showToast("Missing credentials", "Please enter both email and password.");
    return;
  }

  setButtonBusy(dom.loginSubmit, true, "Logging in...");

  try {
    const response = await fetchJson("api/auth.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        action: "login",
        email,
        password,
      }),
    });

    applyBootstrap(response.state);
    dom.loginForm.reset();
    showToast("Login successful", response.message || "You are now signed in.");
    document.getElementById("consultations")?.scrollIntoView({ behavior: "smooth", block: "start" });
  } catch (error) {
    showToast("Login failed", error.message);
  } finally {
    setButtonBusy(dom.loginSubmit, false, "Log in");
  }
}

async function submitRegister(event) {
  event.preventDefault();

  const payload = {
    action: "register",
    full_name: dom.registerName.value.trim(),
    role: dom.registerRole.value,
    email: dom.registerEmail.value.trim(),
    password: dom.registerPassword.value,
    location: dom.registerLocation.value.trim(),
    primary_crop: dom.registerPrimaryCrop.value.trim(),
    specialty: dom.registerSpecialty.value.trim(),
    bio: dom.registerBio.value.trim(),
  };

  if (!payload.full_name || !payload.email || !payload.password) {
    showToast("Missing details", "Please complete the required registration fields.");
    return;
  }

  setButtonBusy(dom.registerSubmit, true, "Creating...");

  try {
    const response = await fetchJson("api/auth.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload),
    });

    applyBootstrap(response.state);
    dom.registerForm.reset();
    syncRegisterRoleFields();
    showToast("Account created", response.message || "Your account is ready.");
    document.getElementById("consultations")?.scrollIntoView({ behavior: "smooth", block: "start" });
  } catch (error) {
    showToast("Registration failed", error.message);
  } finally {
    setButtonBusy(dom.registerSubmit, false, "Create account");
  }
}

async function submitLogout() {
  try {
    const response = await fetchJson("api/auth.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ action: "logout" }),
    });

    applyBootstrap(response.state);
    showToast("Logged out", response.message || "You are now signed out.");
  } catch (error) {
    showToast("Logout failed", error.message);
  }
}

async function submitConsultation(event) {
  event.preventDefault();

  if (!state.permissions.can_create_consultation) {
    showToast("Access denied", "Only farmer accounts can create consultations.");
    return;
  }

  const payload = {
    action: "create",
    title: dom.titleInput.value.trim(),
    crop: dom.cropInput.value.trim(),
    urgency: dom.urgencyInput.value,
    location: dom.locationInput.value.trim(),
    concern: dom.concernInput.value.trim(),
  };

  if (!payload.concern) {
    showToast("Missing concern", "Please describe the farming issue before submitting.");
    return;
  }

  setButtonBusy(dom.consultationSubmit, true, "Submitting...");

  try {
    const response = await fetchJson("api/consultations.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload),
    });

    dom.consultationForm.reset();
    applyBootstrap(response.state);
    state.activeConsultationId = response.consultation?.id || state.activeConsultationId;
    renderConsultationList();
    renderThread();
    showToast("Consultation created", response.message || "Your concern is now in the queue.");
    document.getElementById("consultations")?.scrollIntoView({ behavior: "smooth", block: "start" });
  } catch (error) {
    showToast("Submission failed", error.message);
  } finally {
    setButtonBusy(dom.consultationSubmit, false, "Submit concern");
  }
}

async function submitThreadMessage(event) {
  event.preventDefault();

  const consultation = getActiveConsultation();
  const message = dom.threadInput.value.trim();

  if (!consultation) {
    showToast("No consultation selected", "Choose a consultation first before sending a message.");
    return;
  }

  if (!message) {
    showToast("Missing message", "Please type a message before sending.");
    return;
  }

  let action = "";
  let busyLabel = "";
  let doneLabel = "";

  if (state.auth.role === "farmer") {
    action = "farmer_message";
    busyLabel = "Sending...";
    doneLabel = "Send follow-up";
  } else if (state.auth.role === "consultant") {
    action = "consultant_response";
    busyLabel = "Responding...";
    doneLabel = "Send consultant response";
  } else {
    showToast("Access denied", "This role cannot send thread messages.");
    return;
  }

  setButtonBusy(dom.threadSubmit, true, busyLabel);

  try {
    const response = await fetchJson("api/consultations.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        action,
        consultation_id: consultation.id,
        message,
      }),
    });

    dom.threadInput.value = "";
    applyBootstrap(response.state);
    state.activeConsultationId = response.consultation?.id || consultation.id;
    renderConsultationList();
    renderThread();
    showToast(
      state.auth.role === "consultant" ? "Consultant response sent" : "Update sent",
      response.message || "The consultation thread has new activity."
    );
  } catch (error) {
    showToast("Message failed", error.message);
  } finally {
    setButtonBusy(dom.threadSubmit, false, doneLabel);
  }
}

async function submitAssignment() {
  const consultation = getActiveConsultation();
  const consultantId = Number(dom.assignConsultantSelect.value);

  if (!consultation || !consultantId) {
    showToast("Assignment missing", "Please choose a consultant before saving.");
    return;
  }

  setButtonBusy(dom.assignConsultantButton, true, "Saving...");

  try {
    const response = await fetchJson("api/consultations.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        action: "assign",
        consultation_id: consultation.id,
        consultant_id: consultantId,
      }),
    });

    applyBootstrap(response.state);
    state.activeConsultationId = response.consultation?.id || consultation.id;
    renderConsultationList();
    renderThread();
    showToast("Assignment saved", response.message || "Consultant assignment updated.");
  } catch (error) {
    showToast("Assignment failed", error.message);
  } finally {
    setButtonBusy(dom.assignConsultantButton, false, "Save assignment");
  }
}

async function submitStatusUpdate() {
  const consultation = getActiveConsultation();
  const status = dom.statusSelect.value;

  if (!consultation || !status) {
    showToast("Status missing", "Please choose a status before saving.");
    return;
  }

  setButtonBusy(dom.updateStatusButton, true, "Saving...");

  try {
    const response = await fetchJson("api/consultations.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        action: "status",
        consultation_id: consultation.id,
        status,
      }),
    });

    applyBootstrap(response.state);
    state.activeConsultationId = response.consultation?.id || consultation.id;
    renderConsultationList();
    renderThread();
    showToast("Status saved", response.message || "Consultation status updated.");
  } catch (error) {
    showToast("Status update failed", error.message);
  } finally {
    setButtonBusy(dom.updateStatusButton, false, "Save status");
  }
}

async function submitAssistantMessage(event) {
  event.preventDefault();

  const text = dom.assistantInput.value.trim();
  if (!text) {
    showToast("Missing question", "Please type a farming question for AgroLLM.");
    return;
  }

  state.assistantMessages.push({
    id: `assistant-user-${Date.now()}`,
    sender: "user",
    text,
    time: new Date().toISOString(),
    meta: null,
  });
  renderAssistantMessages();
  dom.assistantInput.value = "";
  setButtonBusy(dom.assistantSubmit, true, "Thinking...");

  try {
    const response = await fetchJson("api/chat.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ message: text }),
    });

    state.assistantMessages.push({
      id: `assistant-ai-${Date.now()}`,
      sender: "ai",
      text: response.assistant?.reply || "No reply available.",
      time: new Date().toISOString(),
      meta: {
        ...(response.assistant || {}),
        original_question: text,
      },
    });
    renderAssistantMessages();
  } catch (error) {
    state.assistantMessages.push({
      id: `assistant-ai-error-${Date.now()}`,
      sender: "ai",
      text: `I could not complete that request right now.\n\n${error.message}`,
      time: new Date().toISOString(),
      meta: null,
    });
    renderAssistantMessages();
  } finally {
    setButtonBusy(dom.assistantSubmit, false, "Send to AgroLLM");
  }
}

async function markNotificationRead(id) {
  try {
    const response = await fetchJson("api/notifications.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ id }),
    });

    if (response.ok && response.state) {
      applyBootstrap(response.state);
    }
  } catch (error) {
    console.error(error);
  }
}

function openAssistant() {
  dom.assistantShell.classList.add("is-open");
  dom.assistantShell.setAttribute("aria-hidden", "false");
  document.body.style.overflow = "hidden";
  window.setTimeout(() => dom.assistantInput?.focus(), 80);
}

function closeAssistant() {
  dom.assistantShell.classList.remove("is-open");
  dom.assistantShell.setAttribute("aria-hidden", "true");
  document.body.style.overflow = "";
}

function prefillConsultationFromAssistant(payload) {
  if (!state.permissions.can_create_consultation) {
    showToast("Farmer access required", "Log in as a farmer to turn this AI answer into a consultation.");
    document.getElementById("access")?.scrollIntoView({ behavior: "smooth", block: "start" });
    return;
  }

  dom.titleInput.value = payload.title || "";
  dom.cropInput.value = payload.crop || dom.cropInput.value;
  dom.concernInput.value = payload.concern || "";
  document.getElementById("consultations")?.scrollIntoView({ behavior: "smooth", block: "start" });
  dom.concernInput.focus();
  showToast("Consultation prefilled", "The AI answer has been copied into the consultation form.");
}

function getActiveConsultation() {
  return state.consultations.find((consultation) => consultation.id === state.activeConsultationId) || null;
}

function scrollMessagesToBottom(element) {
  if (!element) {
    return;
  }

  element.scrollTop = element.scrollHeight;
}

function setThreadFormEnabled(enabled) {
  dom.threadInput.disabled = !enabled;
  dom.threadSubmit.disabled = !enabled;
  dom.threadForm.classList.toggle("is-disabled", !enabled);
}

function setButtonBusy(button, busy, label) {
  if (!button) {
    return;
  }

  button.disabled = busy;
  button.textContent = label;
}

function buildEmptyState(text) {
  const box = document.createElement("div");
  box.className = "empty-state";
  box.textContent = text;
  return box;
}

function showToast(title, body) {
  const toast = document.createElement("article");
  toast.className = "toast";
  toast.innerHTML = `<strong>${escapeHtml(title)}</strong><span>${escapeHtml(body)}</span>`;
  dom.toastStack.appendChild(toast);

  window.setTimeout(() => {
    toast.remove();
  }, 3200);
}

async function fetchJson(url, options = {}) {
  const response = await fetch(url, options);
  const data = await response.json().catch(() => ({}));

  if (!response.ok) {
    throw new Error(data.message || `Request failed with status ${response.status}`);
  }

  return data;
}

function formatDate(value) {
  if (!value) {
    return "Now";
  }

  const normalized = value.includes("T") ? value : value.replace(" ", "T");
  const date = new Date(normalized);

  if (Number.isNaN(date.getTime())) {
    return value;
  }

  return new Intl.DateTimeFormat(undefined, {
    month: "short",
    day: "numeric",
    hour: "numeric",
    minute: "2-digit",
  }).format(date);
}

function capitalize(value) {
  return value ? value.charAt(0).toUpperCase() + value.slice(1) : "";
}

function escapeHtml(value) {
  return String(value ?? "")
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#39;");
}

function escapeAttribute(value) {
  return escapeHtml(value).replaceAll("`", "&#96;");
}
