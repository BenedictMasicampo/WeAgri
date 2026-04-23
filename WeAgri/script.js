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
  dashboardMetrics: null,
  dashboardSource: "",
  dashboardTrends: {
    weather: [],
    soil_health: [],
    market_prices: [],
  },
  dashboardInsight: "",
  marketPrices: [],
  consultants: [],
  activeConsultantId: null,
  consultantMessages: [],
  consultations: [],
  experts: [],
  consultantOptions: [],
  notifications: [],
  knowledgeHighlights: [],
  knowledgeSearch: "",
  admin: null,
  activeConsultationId: null,
  activeAuthView: "login",
  assistantMessages: [
    {
      id: `assistant-${Date.now()}`,
      sender: "ai",
      text:
        "Hello, I am AgroLLM. Tell me what you are seeing in the field, and I will give practical first steps for pests, crop symptoms, soil, fertilizer, or irrigation.",
      meta: null,
      time: new Date().toISOString(),
    },
  ],
  pollHandle: null,
  dashboardPollHandle: null,
  consultantPollHandle: null,
  charts: {},
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
  metricTemperature: document.getElementById("metric-temperature"),
  metricSoilMoisture: document.getElementById("metric-soil-moisture"),
  metricCropHealth: document.getElementById("metric-crop-health"),
  dashboardUpdated: document.getElementById("dashboard-updated"),
  marketPrices: document.getElementById("market-prices"),
  dashboardGreetingTitle: document.getElementById("dashboard-greeting-title"),
  dashboardActiveMetric: document.getElementById("dashboard-active-metric"),
  dashboardResolvedMetric: document.getElementById("dashboard-resolved-metric"),
  dashboardWeatherMetric: document.getElementById("dashboard-weather-metric"),
  dashboardSoilMetric: document.getElementById("dashboard-soil-metric"),
  dashboardHealthMetric: document.getElementById("dashboard-health-metric"),
  dashboardConsultationMetric: document.getElementById("dashboard-consultation-metric"),
  dashboardAiInsight: document.getElementById("dashboard-ai-insight"),
  dashboardMarketTable: document.getElementById("dashboard-market-table"),
  weatherTrendChart: document.getElementById("weather-trend-chart"),
  soilHealthChart: document.getElementById("soil-health-chart"),
  marketPriceChart: document.getElementById("market-price-chart"),
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
  authInitials: document.getElementById("auth-initials"),
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
  registerSoilType: document.getElementById("register-soil-type"),
  registerCommonIssues: document.getElementById("register-common-issues"),
  registerFarmScale: document.getElementById("register-farm-scale"),
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
  feedbackForm: document.getElementById("feedback-form"),
  feedbackHelpfulness: document.getElementById("feedback-helpfulness"),
  feedbackAccuracy: document.getElementById("feedback-accuracy"),
  feedbackComment: document.getElementById("feedback-comment"),
  feedbackSubmit: document.getElementById("feedback-submit"),
  platformFeedbackForm: document.getElementById("platform-feedback-form"),
  platformFeedbackRating: document.getElementById("platform-feedback-rating"),
  platformFeedbackComment: document.getElementById("platform-feedback-comment"),
  platformFeedbackConfirmation: document.getElementById("platform-feedback-confirmation"),
  scrollThreadBottom: document.getElementById("scroll-thread-bottom"),
  expertList: document.getElementById("expert-list"),
  consultantDirectory: document.getElementById("consultant-directory"),
  consultantChatPanel: document.getElementById("consultant-chat-panel"),
  consultantChatAvatar: document.getElementById("consultant-chat-avatar"),
  consultantChatStatus: document.getElementById("consultant-chat-status"),
  consultantChatName: document.getElementById("consultant-chat-name"),
  consultantMessageThread: document.getElementById("consultant-message-thread"),
  consultantChatForm: document.getElementById("consultant-chat-form"),
  consultantChatInput: document.getElementById("consultant-chat-input"),
  consultantChatSend: document.getElementById("consultant-chat-send"),
  notificationList: document.getElementById("notification-list"),
  adminUserList: document.getElementById("admin-user-list"),
  knowledgeList: document.getElementById("knowledge-list"),
  knowledgeSearch: document.getElementById("knowledge-search"),
  knowledgeTopicFilters: document.getElementById("knowledge-topic-filters"),
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
  setupSectionNavObserver();
  updateActiveNav(window.location.hash || "#dashboard");
  switchAuthView(state.activeAuthView);
  syncRegisterRoleFields();
  applyBootstrap(window.WEAGRI_INITIAL_STATE || null);
  refreshState();
  refreshDashboardMetrics();
  refreshConsultants();
  state.pollHandle = window.setInterval(refreshState, 12000);
  state.dashboardPollHandle = window.setInterval(refreshDashboardMetrics, 5000);
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
      updateActiveNav(link.getAttribute("href") || "");
    });
  });

  window.addEventListener("hashchange", () => updateActiveNav(window.location.hash));

  document.querySelectorAll("[data-open-assistant]").forEach((button) => {
    button.addEventListener("click", openAssistant);
  });

  document.querySelectorAll("[data-close-assistant]").forEach((button) => {
    button.addEventListener("click", closeAssistant);
  });

  document.querySelectorAll("[data-auth-view]").forEach((button) => {
    button.addEventListener("click", () => {
      switchAuthView(button.dataset.authView || "login");
      document.getElementById("landing")?.scrollIntoView({ behavior: "smooth", block: "start" });
    });
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
  dom.feedbackForm?.addEventListener("submit", submitFeedback);
  dom.platformFeedbackForm?.addEventListener("submit", submitPlatformFeedback);
  dom.consultantChatForm?.addEventListener("submit", submitConsultantMessage);
  dom.assignConsultantButton?.addEventListener("click", submitAssignment);
  dom.updateStatusButton?.addEventListener("click", submitStatusUpdate);
  dom.assistantForm?.addEventListener("submit", submitAssistantMessage);
  dom.scrollThreadBottom?.addEventListener("click", () => scrollMessagesToBottom(dom.threadMessages));
  dom.knowledgeSearch?.addEventListener("input", () => {
    state.knowledgeSearch = dom.knowledgeSearch.value.trim();
    renderKnowledge();
  });

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

function setupSectionNavObserver() {
  const sections = ["dashboard", "experts", "consultations", "knowledge", "feedback", "contact"]
    .map((id) => document.getElementById(id))
    .filter(Boolean);

  if (!sections.length) {
    return;
  }

  const observer = new IntersectionObserver(
    (entries) => {
      const visible = entries
        .filter((entry) => entry.isIntersecting)
        .sort((a, b) => b.intersectionRatio - a.intersectionRatio)[0];

      if (visible?.target?.id) {
        updateActiveNav(`#${visible.target.id}`);
      }
    },
    { threshold: [0.28, 0.5], rootMargin: "-18% 0px -55% 0px" }
  );

  sections.forEach((section) => observer.observe(section));
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

async function refreshDashboardMetrics() {
  try {
    const response = await fetchJson("api/v1/get_dashboard.php");
    state.dashboardMetrics = response.metrics || null;
    state.dashboardSource = response.source || "";
    state.dashboardTrends = {
      weather: Array.isArray(response.trends?.weather) ? response.trends.weather : [],
      soil_health: Array.isArray(response.trends?.soil_health) ? response.trends.soil_health : [],
      market_prices: Array.isArray(response.trends?.market_prices) ? response.trends.market_prices : [],
    };
    state.dashboardInsight = response.insight || "";
    state.marketPrices = Array.isArray(response.market_prices) ? response.market_prices : [];
    renderDashboardMetrics(response);
  } catch (error) {
    console.error(error);
    if (dom.dashboardUpdated) {
      dom.dashboardUpdated.textContent = "Live feed unavailable";
    }
  }
}

async function refreshConsultants() {
  try {
    const response = await fetchJson("api/v1/consultants.php");
    state.consultants = Array.isArray(response.consultants) ? response.consultants : [];
    renderConsultantDirectory();
  } catch (error) {
    console.error(error);
  }
}

async function refreshConsultantMessages() {
  if (!state.activeConsultantId) {
    return;
  }

  try {
    const response = await fetchJson(`api/v1/get_messages.php?consultant_id=${encodeURIComponent(state.activeConsultantId)}`);
    state.consultantMessages = Array.isArray(response.messages) ? response.messages : [];
    renderConsultantChat();
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
  renderAppVisibility();
  renderSignals();
  renderDashboardMetrics();
  renderAuthSummary();
  renderConsultationAccess();
  renderConsultationList();
  renderThread();
  renderExperts();
  renderNotifications();
  renderAdminOverview();
  renderKnowledge();
  renderAssistantMessages();
  renderConsultantDirectory();
}

function renderAppVisibility() {
  document.body.classList.toggle("is-authenticated", state.auth.authenticated);
  document.body.classList.toggle("is-guest", !state.auth.authenticated);
}

function updateActiveNav(hash) {
  const normalizedHash = hash || "#dashboard";
  document.querySelectorAll("#site-nav a").forEach((link) => {
    link.classList.toggle("is-active", link.getAttribute("href") === normalizedHash);
  });
}

function renderConsultantDirectory() {
  if (!dom.consultantDirectory) {
    return;
  }

  dom.consultantDirectory.innerHTML = "";

  if (!state.consultants.length) {
    dom.consultantDirectory.appendChild(buildEmptyState("Consultants are loading. You can still ask AgroLLM while waiting."));
    return;
  }

  state.consultants.forEach((consultant) => {
    const card = document.createElement("article");
    card.className = "consultant-profile-card";
    if (consultant.id === state.activeConsultantId) {
      card.classList.add("is-active");
    }

    const initials = getInitials(consultant.name || "Expert");
    const status = consultant.is_online ? "Online" : "Offline";

    card.innerHTML = `
      <div class="consultant-card-top">
        <div class="consultant-avatar">${escapeHtml(initials)}</div>
        <span class="consultant-status ${consultant.is_online ? "is-online" : "is-offline"}">
          <span></span>${escapeHtml(status)}
        </span>
      </div>
      <h3>${escapeHtml(consultant.name || "Agricultural Consultant")}</h3>
      <div class="consultant-specialty">${escapeHtml(consultant.specialty || "General Agronomy")}</div>
      <div class="consultant-rating" aria-label="Consultant rating">${renderStars(consultant.rating || 4.8)} <span>${formatNumber(consultant.rating || 4.8, 1)}</span></div>
      <button type="button" class="button consultant-chat-button">CHAT</button>
    `;

    card.querySelector(".consultant-chat-button")?.addEventListener("click", () => openConsultantChat(consultant.id));
    dom.consultantDirectory.appendChild(card);
  });
}

function openConsultantChat(consultantId) {
  state.activeConsultantId = Number(consultantId);
  state.consultantMessages = [];
  renderConsultantDirectory();
  renderConsultantChat();
  refreshConsultantMessages();

  if (state.consultantPollHandle) {
    window.clearInterval(state.consultantPollHandle);
  }

  state.consultantPollHandle = window.setInterval(refreshConsultantMessages, 2000);
  dom.consultantChatInput?.focus();
}

function renderConsultantChat() {
  if (!dom.consultantMessageThread) {
    return;
  }

  const consultant = getActiveConsultant();

  if (!consultant) {
    dom.consultantChatName.textContent = "Human consultant chat";
    dom.consultantChatStatus.textContent = "Select an expert";
    dom.consultantChatAvatar.textContent = "WA";
    dom.consultantMessageThread.innerHTML = "";
    dom.consultantMessageThread.appendChild(buildEmptyState("Choose a consultant card to begin a direct chat."));
    if (dom.consultantChatInput) {
      dom.consultantChatInput.disabled = true;
    }
    if (dom.consultantChatSend) {
      dom.consultantChatSend.disabled = true;
    }
    return;
  }

  dom.consultantChatName.textContent = consultant.name || "Agricultural Consultant";
  dom.consultantChatStatus.textContent = consultant.is_online ? "Active Now" : "Offline - replies may take longer";
  dom.consultantChatAvatar.textContent = getInitials(consultant.name || "Expert");
  dom.consultantChatInput.disabled = false;
  dom.consultantChatSend.disabled = false;
  dom.consultantMessageThread.innerHTML = "";

  if (!state.consultantMessages.length) {
    dom.consultantMessageThread.appendChild(buildEmptyState("No messages yet. Send the crop, symptoms, and location to begin."));
    return;
  }

  state.consultantMessages.forEach((message) => {
    const bubble = document.createElement("article");
    bubble.className = `consultant-message ${message.sender_type === "consultant" ? "is-consultant" : "is-farmer"}`;
    bubble.innerHTML = `
      <p>${escapeHtml(message.message_text || "")}</p>
      <span>${formatDate(message.created_at)}</span>
    `;
    dom.consultantMessageThread.appendChild(bubble);
  });

  scrollMessagesToBottom(dom.consultantMessageThread);
}

function getActiveConsultant() {
  return state.consultants.find((consultant) => consultant.id === state.activeConsultantId) || null;
}

function renderStars(value) {
  const rating = Math.round(Number(value) || 5);
  return Array.from({ length: 5 }, (_, index) => (index < rating ? "*" : ".")).join("");
}

function renderDashboardMetrics(payload = null) {
  const metrics = state.dashboardMetrics;

  if (dom.dashboardGreetingTitle) {
    const name = state.auth.authenticated && state.auth.user?.full_name
      ? state.auth.user.full_name.split(/\s+/)[0]
      : "Farmer";
    dom.dashboardGreetingTitle.textContent = `Hello, ${name}.`;
  }

  const resolvedCount = state.consultations.filter((consultation) => consultation.status === "resolved").length;
  if (dom.dashboardActiveMetric) {
    dom.dashboardActiveMetric.textContent = String(state.stats.activeConsultations || 0);
  }
  if (dom.dashboardResolvedMetric) {
    dom.dashboardResolvedMetric.textContent = String(resolvedCount);
  }

  if (!metrics) {
    if (dom.metricTemperature) {
      dom.metricTemperature.textContent = "-- C";
    }
    if (dom.metricSoilMoisture) {
      dom.metricSoilMoisture.textContent = "--%";
    }
    if (dom.metricCropHealth) {
      dom.metricCropHealth.textContent = "--%";
    }
    if (dom.dashboardWeatherMetric) {
      dom.dashboardWeatherMetric.textContent = "-- C";
    }
    if (dom.dashboardSoilMetric) {
      dom.dashboardSoilMetric.textContent = "--%";
    }
    if (dom.dashboardHealthMetric) {
      dom.dashboardHealthMetric.textContent = "--%";
    }
    if (dom.dashboardUpdated) {
      dom.dashboardUpdated.textContent = "Connecting...";
    }
    return;
  }

  if (dom.metricTemperature) {
    dom.metricTemperature.textContent = `${formatNumber(metrics.temperature, 1)} C`;
  }
  if (dom.metricSoilMoisture) {
    dom.metricSoilMoisture.textContent = `${formatNumber(metrics.soil_moisture, 1)}%`;
  }
  if (dom.metricCropHealth) {
    dom.metricCropHealth.textContent = `${formatNumber(metrics.crop_health, 1)}%`;
  }
  if (dom.dashboardWeatherMetric) {
    dom.dashboardWeatherMetric.textContent = `${formatNumber(metrics.temperature, 1)} C`;
  }
  if (dom.dashboardSoilMetric) {
    dom.dashboardSoilMetric.textContent = `${formatNumber(metrics.soil_moisture, 1)}%`;
  }
  if (dom.dashboardHealthMetric) {
    dom.dashboardHealthMetric.textContent = `${formatNumber(metrics.crop_health, 1)}%`;
  }

  if (dom.dashboardUpdated) {
    const source = payload?.source || state.dashboardSource;
    const sourceLabel = source === "mysql" ? "Live MySQL" : "Demo data";
    dom.dashboardUpdated.textContent = `${sourceLabel} - ${formatDate(metrics.timestamp)}`;
  }

  if (dom.marketPrices) {
    if (!state.marketPrices.length) {
      dom.marketPrices.innerHTML = '<span class="market-empty">No market prices yet</span>';
    } else {
      dom.marketPrices.innerHTML = state.marketPrices
        .map((item) => {
          const trend = item.trend === "down" ? "down" : "up";
          return `
            <span class="market-pill market-pill-${trend}">
              <strong>${escapeHtml(item.crop_name)}</strong>
              <span>${formatPhp(item.price)} ${trend}</span>
            </span>
          `;
        })
        .join("");
    }
  }

  if (dom.dashboardAiInsight) {
    dom.dashboardAiInsight.textContent =
      state.dashboardInsight ||
      "Field readings are loading. AgroLLM will summarize weather, soil, crop, and market signals here.";
  }

  if (dom.dashboardMarketTable) {
    renderDashboardMarketTable();
  }

  renderDashboardCharts();
}

function renderDashboardMarketTable() {
  if (!state.marketPrices.length) {
    dom.dashboardMarketTable.innerHTML = '<tr><td colspan="3">No market prices yet.</td></tr>';
    return;
  }

  dom.dashboardMarketTable.innerHTML = state.marketPrices
    .map((item) => {
      const trend = item.trend === "down" ? "down" : item.trend === "stable" ? "stable" : "up";
      return `
        <tr>
          <td>${escapeHtml(item.crop_name)}</td>
          <td>${formatPhp(item.price)}</td>
          <td><span class="trend-pill trend-${trend}">${escapeHtml(trend)}</span></td>
        </tr>
      `;
    })
    .join("");
}

function renderDashboardCharts() {
  if (!window.Chart) {
    return;
  }

  const weather = state.dashboardTrends.weather || [];
  const soil = state.dashboardTrends.soil_health || [];
  const market = state.dashboardTrends.market_prices || [];

  upsertChart("weather", dom.weatherTrendChart, {
    type: "line",
    labels: weather.map((point) => point.label || ""),
    datasets: [
      {
        label: "Temperature C",
        data: weather.map((point) => Number(point.value || 0)),
        borderColor: "#10B981",
        backgroundColor: "rgba(16, 185, 129, 0.12)",
        tension: 0.35,
        fill: true,
        pointRadius: 3,
        pointHoverRadius: 5,
      },
    ],
  });

  upsertChart("soil", dom.soilHealthChart, {
    type: "line",
    labels: soil.map((point) => point.label || ""),
    datasets: [
      {
        label: "Soil moisture %",
        data: soil.map((point) => Number(point.soil_moisture || 0)),
        borderColor: "#059669",
        backgroundColor: "rgba(5, 150, 105, 0.08)",
        tension: 0.35,
        fill: true,
        pointRadius: 3,
      },
      {
        label: "Crop health %",
        data: soil.map((point) => Number(point.crop_health || 0)),
        borderColor: "#8D7654",
        backgroundColor: "rgba(141, 118, 84, 0.08)",
        tension: 0.35,
        fill: false,
        pointRadius: 3,
      },
    ],
  });

  upsertChart("market", dom.marketPriceChart, {
    type: "bar",
    labels: market.map((point) => point.label || ""),
    datasets: [
      {
        label: "PHP per kg",
        data: market.map((point) => Number(point.value || 0)),
        backgroundColor: market.map((point) =>
          point.trend === "down"
            ? "rgba(141, 118, 84, 0.32)"
            : point.trend === "stable"
              ? "rgba(100, 116, 139, 0.22)"
              : "rgba(16, 185, 129, 0.32)"
        ),
        borderColor: market.map((point) =>
          point.trend === "down"
            ? "#8D7654"
            : point.trend === "stable"
              ? "#64748B"
              : "#10B981"
        ),
        borderWidth: 1,
        borderRadius: 14,
      },
    ],
  });
}

function upsertChart(key, canvas, chartData) {
  if (!canvas) {
    return;
  }

  const labels = chartData.labels.length ? chartData.labels : ["No data"];
  const datasets = chartData.datasets.map((dataset) => ({
    ...dataset,
    data: dataset.data.length ? dataset.data : [0],
  }));

  if (state.charts[key]) {
    state.charts[key].data.labels = labels;
    state.charts[key].data.datasets = datasets;
    state.charts[key].update();
    return;
  }

  state.charts[key] = new Chart(canvas.getContext("2d"), {
    type: chartData.type,
    data: {
      labels,
      datasets,
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          labels: {
            color: "#64748B",
            boxWidth: 10,
            usePointStyle: true,
          },
        },
      },
      scales: {
        x: {
          grid: {
            display: false,
          },
          ticks: {
            color: "#64748B",
          },
          border: {
            color: "#E2E8F0",
          },
        },
        y: {
          grid: {
            color: "#E2E8F0",
          },
          ticks: {
            color: "#64748B",
          },
          border: {
            color: "#E2E8F0",
          },
        },
      },
    },
  });
}

function renderSignals() {
  if (dom.sourcePill) {
    dom.sourcePill.textContent = state.sourceLabel || "Loading...";
  }
  if (dom.signalOnlineExperts) {
    dom.signalOnlineExperts.textContent = String(state.stats.onlineExperts);
  }
  if (dom.signalActiveConsultations) {
    dom.signalActiveConsultations.textContent = String(state.stats.activeConsultations);
  }
  if (dom.signalResponseTime) {
    dom.signalResponseTime.textContent = `${state.stats.averageResponseMinutes || 0}m`;
  }
  if (dom.signalUnreadAlerts) {
    dom.signalUnreadAlerts.textContent = String(state.stats.unreadNotifications);
  }
  if (dom.navUserChip) {
    dom.navUserChip.textContent = state.auth.authenticated
      ? `${state.auth.user?.full_name || "User"} (${state.auth.roleLabel})`
      : "Guest";
  }
  dom.navLogoutButton?.classList.toggle("is-hidden", !state.auth.authenticated);

  const spotlight = state.consultations[0];
  if (!spotlight) {
    if (dom.heroThreadTitle) {
      dom.heroThreadTitle.textContent = state.auth.authenticated
        ? "No consultation available yet"
        : "Sign in to view consultations";
    }
    if (dom.heroThreadStatus) {
      dom.heroThreadStatus.textContent = state.auth.authenticated ? "Waiting" : "Guest view";
    }
    if (dom.heroThreadExpert) {
      dom.heroThreadExpert.textContent = "No expert assigned";
    }
    if (dom.heroThreadPreview) {
      dom.heroThreadPreview.textContent = state.auth.authenticated
        ? "Create or open a consultation to start the case flow."
        : "Consultation details stay private until you log in.";
    }
    return;
  }

  if (dom.heroThreadTitle) {
    dom.heroThreadTitle.textContent = spotlight.title;
  }
  if (dom.heroThreadStatus) {
    dom.heroThreadStatus.textContent = spotlight.status_label || spotlight.status;
  }
  if (dom.heroThreadExpert) {
    dom.heroThreadExpert.textContent = spotlight.assigned_expert_name
      ? `Expert: ${spotlight.assigned_expert_name}`
      : "AI triage active";
  }
  if (dom.heroThreadPreview) {
    dom.heroThreadPreview.textContent = spotlight.last_message_preview || spotlight.summary || "";
  }
}

function renderAuthSummary() {
  const user = state.auth.user;
  dom.authRoleBadge.textContent = state.auth.roleLabel;

  if (!state.auth.authenticated || !user) {
    dom.authSummaryTitle.textContent = "Continue as guest";
    dom.authSummaryCopy.textContent =
      "Log in to create consultations, respond as a consultant, or manage assignments as an administrator.";
    dom.authName.textContent = "Guest user";
    dom.authEmail.textContent = "Not signed in";
    dom.authRoleDetail.textContent = "Public access only";
    if (dom.authInitials) {
      dom.authInitials.textContent = "G";
    }
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
        ? `${user.location || "Farm location"} | ${user.primary_crop || "General farming"} | ${formatFarmScale(user.farm_scale)}`
        : state.auth.role === "consultant"
          ? `${user.specialty || "General Agronomy"}`
          : "Admin access";
    if (dom.authInitials) {
      dom.authInitials.textContent = getInitials(user.full_name || user.email || "User");
    }
    dom.logoutButton.classList.remove("is-hidden");
  }

  if (dom.authCapabilities) {
    dom.authCapabilities.innerHTML = "";
    if (state.auth.authenticated && user && state.auth.role === "farmer") {
      [
        `Soil: ${user.soil_type || "Not specified"}`,
        `Common issues: ${user.common_issues || "Not specified"}`,
      ].forEach((line) => {
        const card = document.createElement("article");
        card.className = "compact-card notification-card";
        card.innerHTML = `<p>${escapeHtml(line)}</p>`;
        dom.authCapabilities.appendChild(card);
      });
      dom.authCapabilities.classList.remove("is-hidden");
    } else {
      dom.authCapabilities.classList.add("is-hidden");
    }
  }
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
    const card = document.createElement("article");
    card.className = "consultation-item";
    if (consultation.id === state.activeConsultationId) {
      card.classList.add("is-active");
    }

    card.innerHTML = `
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
      <div class="consultation-card-actions">
        <button type="button" class="text-button" data-consultation-action="view">View Details</button>
        <button type="button" class="text-button" data-consultation-action="update">Update</button>
        <button type="button" class="text-button" data-consultation-action="expert">Talk to Expert</button>
      </div>
    `;

    card.querySelectorAll("[data-consultation-action]").forEach((actionButton) => {
      actionButton.addEventListener("click", () => {
        state.activeConsultationId = consultation.id;
        renderConsultationList();
        renderThread();

        if (actionButton.dataset.consultationAction === "update") {
          dom.threadInput?.focus();
        }

        if (actionButton.dataset.consultationAction === "expert") {
          if (!state.auth.authenticated) {
            showToast("Login required", "Log in to continue the consultation with an expert.");
          } else {
            dom.threadInput.value = dom.threadInput.value || "I would like to talk to an expert about this case.";
            dom.threadInput?.focus();
          }
        }

        document.getElementById("consultations")?.scrollIntoView({ behavior: "smooth", block: "start" });
      });
    });

    card.addEventListener("click", (event) => {
      if (event.target?.closest?.("button")) {
        return;
      }
      state.activeConsultationId = consultation.id;
      renderConsultationList();
      renderThread();
    });

    dom.consultationList.appendChild(card);
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
    dom.feedbackForm?.classList.add("is-hidden");
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
    consultation.farmer_profile?.soil_type ? `Soil: ${consultation.farmer_profile.soil_type}` : "",
    consultation.farmer_profile?.farm_scale_label ? `Scale: ${consultation.farmer_profile.farm_scale_label}` : "",
    consultation.farmer_profile?.common_issues ? `Common issues: ${consultation.farmer_profile.common_issues}` : "",
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
      if (isEscalationMessage(message.message || "")) {
        card.classList.add("is-escalation");
      }

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

  if (consultation.feedback) {
    const feedbackCard = document.createElement("article");
    feedbackCard.className = "message-card";
    feedbackCard.dataset.sender = "ai";
    feedbackCard.innerHTML = `
      <div class="message-top">
        <span class="message-author">Feedback</span>
        <span class="message-time">${formatDate(consultation.feedback.created_at)}</span>
      </div>
      <p>Helpfulness ${escapeHtml(String(consultation.feedback.helpfulness_rating))}/5 | Accuracy ${escapeHtml(String(consultation.feedback.accuracy_rating))}/5</p>
      ${
        consultation.feedback.comment
          ? `<p>${escapeHtml(consultation.feedback.comment)}</p>`
          : ""
      }
    `;
    dom.threadMessages.appendChild(feedbackCard);
  }

  renderFeedbackPrompt(consultation);

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

function renderFeedbackPrompt(consultation) {
  if (!dom.feedbackForm) {
    return;
  }

  const isFarmerOwner =
    state.auth.role === "farmer" &&
    Number(state.auth.user?.linked_farmer_id || 0) === Number(consultation.farmer_id || 0);

  if (!isFarmerOwner || !consultation.can_submit_feedback) {
    dom.feedbackForm.classList.add("is-hidden");
    dom.feedbackForm.dataset.consultationId = "";
    return;
  }

  dom.feedbackForm.classList.remove("is-hidden");
  if (dom.feedbackForm.dataset.consultationId !== String(consultation.id)) {
    dom.feedbackHelpfulness.value = "5";
    dom.feedbackAccuracy.value = "5";
    dom.feedbackComment.value = "";
    dom.feedbackForm.dataset.consultationId = String(consultation.id);
  }
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

  const feedback = state.admin.feedback || {};
  const feedbackCard = document.createElement("article");
  feedbackCard.className = "notification-card";
  feedbackCard.innerHTML = `
    <div class="notification-top">
      <span class="notification-type">Feedback</span>
    </div>
    <h4>${escapeHtml(String(feedback.count || 0))} ratings collected</h4>
    <p>Helpfulness ${escapeHtml(String(feedback.avg_helpfulness || 0))}/5 | Accuracy ${escapeHtml(String(feedback.avg_accuracy || 0))}/5</p>
    <div class="pill-row">
      ${(feedback.trending_feedback_terms || [])
        .map((term) => `<span class="topic-pill">${escapeHtml(term)}</span>`)
        .join("")}
    </div>
    ${
      (feedback.ai_knowledge_gap_terms || []).length
        ? `<p>AI improvement signals: ${escapeHtml(feedback.ai_knowledge_gap_terms.join(", "))}</p>`
        : ""
    }
  `;
  dom.adminUserList.appendChild(feedbackCard);

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
  if (!dom.knowledgeList) {
    return;
  }

  dom.knowledgeList.innerHTML = "";
  if (dom.knowledgeTopicFilters) {
    dom.knowledgeTopicFilters.innerHTML = "";
  }

  if (!state.knowledgeHighlights.length) {
    dom.knowledgeList.appendChild(buildEmptyState("Knowledge highlights will appear here after loading."));
    return;
  }

  const topics = Array.from(new Set(state.knowledgeHighlights.map((entry) => entry.topic || "Knowledge base")));
  if (dom.knowledgeTopicFilters) {
    topics.slice(0, 8).forEach((topic) => {
      const button = document.createElement("button");
      button.type = "button";
      button.className = "assistant-inline-action";
      button.textContent = topic;
      button.addEventListener("click", () => {
        state.knowledgeSearch = topic;
        if (dom.knowledgeSearch) {
          dom.knowledgeSearch.value = topic;
        }
        renderKnowledge();
      });
      dom.knowledgeTopicFilters.appendChild(button);
    });
  }

  const query = state.knowledgeSearch.toLowerCase();
  const entries = query
    ? state.knowledgeHighlights.filter((entry) =>
        [entry.title, entry.topic, entry.source, entry.excerpt, ...(entry.recommendations || [])]
          .join(" ")
          .toLowerCase()
          .includes(query)
      )
    : state.knowledgeHighlights;

  if (!entries.length) {
    dom.knowledgeList.appendChild(buildEmptyState("No knowledge entry matched that search."));
    return;
  }

  entries.forEach((entry) => {
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
    if (isEscalationMessage(message.text) || message.meta?.escalate_to_expert) {
      article.classList.add("is-escalation");
    }

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
              Request consultant review
            </button>
          </div>
        `
        : "";

    const references = Array.isArray(message.meta?.references) && message.meta.references.length
      ? `<div class="pill-row">${message.meta.references
          .map((reference) => `<span class="reference-pill">${escapeHtml(reference)}</span>`)
          .join("")}</div>`
      : "";
    const availableQuickActions = Array.isArray(message.meta?.quick_actions)
      ? message.meta.quick_actions.filter((action) => !(message.meta?.escalate_to_expert && action.action === "consultation:create"))
      : [];
    const quickActions =
      message.sender === "ai" && availableQuickActions.length
        ? `
          <div class="assistant-actions-row">
            ${availableQuickActions
              .map((action) => {
                const payload = {
                  action: action.action || "",
                  prompt: action.prompt || "",
                  title: message.meta?.suggested_title || "",
                  crop: message.meta?.crop || "",
                  concern: message.meta?.original_question || action.prompt || "",
                };
                return `<button type="button" class="assistant-inline-action" data-quick-action="${escapeAttribute(JSON.stringify(payload))}">${escapeHtml(action.label || "Open")}</button>`;
              })
              .join("")}
          </div>
        `
        : "";

    article.innerHTML = `
      <div class="assistant-meta">
        <span class="message-author">${message.sender === "user" ? "You" : "AgroLLM"}</span>
        <span class="message-time">${message.time ? formatDate(message.time) : "Just now"}</span>
      </div>
      <p>${escapeHtml(message.text)}</p>
      ${references}
      ${quickActions}
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

  dom.assistantMessages.querySelectorAll("[data-quick-action]").forEach((button) => {
    button.addEventListener("click", () => {
      const payload = JSON.parse(button.dataset.quickAction || "{}");

      if (payload.action === "consultation:create") {
        prefillConsultationFromAssistant(payload);
        closeAssistant();
        return;
      }

      if (payload.prompt) {
        dom.assistantInput.value = payload.prompt;
        dom.assistantForm.requestSubmit();
      }
    });
  });

  scrollMessagesToBottom(dom.assistantMessages);
}

function isEscalationMessage(text) {
  return String(text || "").toLowerCase().includes("let me escalate this to our human agricultural consultants");
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
    document.getElementById("dashboard")?.scrollIntoView({ behavior: "smooth", block: "start" });
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
    soil_type: dom.registerSoilType.value.trim(),
    common_issues: dom.registerCommonIssues.value.trim(),
    farm_scale: dom.registerFarmScale.value,
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
    document.getElementById("dashboard")?.scrollIntoView({ behavior: "smooth", block: "start" });
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

async function submitFeedback(event) {
  event.preventDefault();

  const consultation = getActiveConsultation();
  if (!consultation) {
    showToast("No consultation selected", "Choose a consultation before rating advice.");
    return;
  }

  const payload = {
    action: "feedback",
    consultation_id: consultation.id,
    rating: Number(dom.feedbackHelpfulness.value),
    accuracy: Number(dom.feedbackAccuracy.value),
    comment: dom.feedbackComment.value.trim(),
  };

  setButtonBusy(dom.feedbackSubmit, true, "Submitting...");

  try {
    const response = await fetchJson("api/consultations.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload),
    });

    applyBootstrap(response.state);
    state.activeConsultationId = response.consultation?.id || consultation.id;
    renderConsultationList();
    renderThread();
    showToast("Feedback saved", response.message || "Thank you for helping improve WeAgri.");
  } catch (error) {
    showToast("Feedback failed", error.message);
  } finally {
    setButtonBusy(dom.feedbackSubmit, false, "Submit feedback");
  }
}

function submitPlatformFeedback(event) {
  event.preventDefault();

  dom.platformFeedbackConfirmation?.classList.remove("is-hidden");
  showToast(
    "Feedback received",
    `Thank you for rating WeAgri ${dom.platformFeedbackRating?.value || "5"} out of 5.`
  );
  if (dom.platformFeedbackComment) {
    dom.platformFeedbackComment.value = "";
  }
}

async function submitConsultantMessage(event) {
  event.preventDefault();

  const consultant = getActiveConsultant();
  const message = dom.consultantChatInput?.value.trim() || "";

  if (!consultant) {
    showToast("Choose an expert", "Select a consultant before sending a message.");
    return;
  }

  if (!message) {
    showToast("Missing message", "Type a short message for the consultant.");
    return;
  }

  setButtonBusy(dom.consultantChatSend, true, "Sending...");

  try {
    const response = await fetchJson("api/v1/send_message.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        consultant_id: consultant.id,
        message_text: message,
      }),
    });

    if (!response.ok) {
      throw new Error(response.message || "Message could not be sent.");
    }

    dom.consultantChatInput.value = "";
    await refreshConsultantMessages();
  } catch (error) {
    showToast("Message failed", error.message);
  } finally {
    setButtonBusy(dom.consultantChatSend, false, "Send");
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
    showToast("Missing question", "Please type a farming question.");
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
    setButtonBusy(dom.assistantSubmit, false, "Send");
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
    document.getElementById("landing")?.scrollIntoView({ behavior: "smooth", block: "start" });
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

function formatNumber(value, digits = 0) {
  const number = Number(value);

  if (!Number.isFinite(number)) {
    return "--";
  }

  return new Intl.NumberFormat(undefined, {
    minimumFractionDigits: digits,
    maximumFractionDigits: digits,
  }).format(number);
}

function formatPhp(value) {
  const number = Number(value);

  if (!Number.isFinite(number)) {
    return "PHP --";
  }

  return `PHP ${new Intl.NumberFormat("en-PH", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(number)}`;
}

function capitalize(value) {
  return value ? value.charAt(0).toUpperCase() + value.slice(1) : "";
}

function formatFarmScale(value) {
  const labels = {
    smallholder: "Smallholder",
    commercial: "Commercial",
    backyard: "Backyard",
    cooperative: "Cooperative",
  };

  return labels[value] || "Smallholder";
}

function getInitials(value) {
  const parts = String(value || "")
    .trim()
    .split(/\s+/)
    .filter(Boolean);

  if (!parts.length) {
    return "U";
  }

  return parts
    .slice(0, 2)
    .map((part) => part.charAt(0).toUpperCase())
    .join("");
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
