(function () {
  const data = window.DEKPOKE_CREDITS;
  const root = document.getElementById("creditsRoll");

  if (!data || !root) return;

  function el(tag, className, text) {
    const node = document.createElement(tag);
    if (className) node.className = className;
    if (text !== undefined && text !== null) node.textContent = text;
    return node;
  }

  function extractRoleId(values) {
    const text = (Array.isArray(values) ? values.join(" ") : String(values || ""));
    const match = text.match(/\b\d{17,32}\b/);
    return match ? match[0] : "";
  }

  function section(title) {
    const sec = el("section", "section");
    sec.appendChild(el("h2", "section-title", title));
    return sec;
  }

  function roleBlock(role, names, note, sourceRoleId) {
    const wrap = el("div", "role");
    const roleId = sourceRoleId || extractRoleId([role, note].concat(names || []));

    wrap.appendChild(el("div", "role-name", role));

    if (roleId) {
      const dynamic = el("div", "person dynamic-role-members", "Loading members from role " + roleId + "...");
      dynamic.dataset.roleId = roleId;
      wrap.appendChild(dynamic);
    } else {
      (names || []).forEach((name) => wrap.appendChild(el("div", "person", name)));
    }

    if (note) wrap.appendChild(el("div", "note", note));
    return wrap;
  }

  function listBlock(items) {
    const wrap = el("div", "list");
    (items || []).forEach((item) => wrap.appendChild(el("div", "item", item)));
    return wrap;
  }

  function pillBlock(items) {
    const wrap = el("div", "pill-list");
    (items || []).forEach((item) => wrap.appendChild(el("span", "pill", item)));
    return wrap;
  }

  function addIntro() {
    root.appendChild(el("div", "kicker", data.kicker));
    root.appendChild(el("h1", "main-title", data.title));
    const sub = el("p", "subtitle");
    (data.subtitle || []).forEach((line, index) => {
      if (index) sub.appendChild(document.createElement("br"));
      sub.appendChild(document.createTextNode(line));
    });
    root.appendChild(sub);
  }

  function addCoreTeam() {
    const sec = section("Core Team");
    (data.coreTeam || []).forEach((entry) => sec.appendChild(roleBlock(entry.role, entry.names, entry.note, entry.sourceRoleId)));
    root.appendChild(sec);
  }

  function addRoleSources() {
    if (!data.roleSources || !data.roleSources.length) return;
    const sec = section("Discord Role Sources");
    data.roleSources.forEach((entry) => {
      sec.appendChild(roleBlock(entry.role, entry.names, entry.note, entry.sourceRoleId));
    });
    root.appendChild(sec);
  }

  function addDepartment(dep) {
    const sec = section(dep.title);
    if (dep.type === "list") {
      sec.appendChild(listBlock(dep.items));
    } else if (dep.type === "pills") {
      sec.appendChild(pillBlock(dep.items));
    } else if (dep.type === "department") {
      sec.appendChild(roleBlock(dep.role, dep.people, dep.note, dep.sourceRoleId));
      sec.appendChild(pillBlock(dep.pills));
    } else if (dep.type === "roles") {
      (dep.entries || []).forEach((entry) => {
        sec.appendChild(roleBlock(entry.role, entry.names, entry.note, entry.sourceRoleId));
      });
      if (dep.pills && dep.pills.length) sec.appendChild(pillBlock(dep.pills));
    }
    root.appendChild(sec);
  }

  function addSupporters() {
    const sec = section("Supporters");
    const grid = el("div", "supporter-grid");
    Object.entries(data.supporters || {}).forEach(([group, names]) => {
      const card = el("div", "support-card");
      card.appendChild(el("strong", "", group));
      (names || []).forEach((name) => card.appendChild(el("span", "", name)));
      grid.appendChild(card);
    });
    sec.appendChild(grid);
    root.appendChild(sec);
  }

  function addBotSystem() {
    const sec = section("Bot Systems Used");
    sec.appendChild(roleBlock(data.botSystem.mainRole, [data.botSystem.mainBot], data.botSystem.note, data.botSystem.sourceRoleId));
    sec.appendChild(pillBlock(data.botSystem.bots));
    root.appendChild(sec);
  }

  function addSimplePillSection(title, items) {
    const sec = section(title);
    sec.appendChild(pillBlock(items));
    root.appendChild(sec);
  }

  function addParagraphSection(title, lines) {
    const sec = section(title);
    const p = el("p", title === "Legal" ? "legal" : "subtitle");
    (lines || []).forEach((line, index) => {
      if (index) p.appendChild(document.createElement("br"));
      p.appendChild(document.createTextNode(line));
    });
    sec.appendChild(p);
    root.appendChild(sec);
    return sec;
  }

  function renderDynamicMembers(target, members) {
    target.textContent = "";

    if (!members || !members.length) {
      target.textContent = "No active members found for this role.";
      return;
    }

    members.forEach((member) => {
      target.appendChild(el("div", "", member.displayName || member.userName || member.userId));
    });
  }

  function loadDynamicRoleMembers() {
    const targets = Array.from(document.querySelectorAll(".dynamic-role-members[data-role-id]"));
    if (!targets.length) return;

    const roleIds = Array.from(new Set(targets.map((target) => target.dataset.roleId).filter(Boolean)));
    if (!roleIds.length) return;

    const apiUrl = data.roleMemberApi || "../api/credits/role-members.php";
    const separator = apiUrl.includes("?") ? "&" : "?";
    const url = apiUrl + separator + "roleIds=" + encodeURIComponent(roleIds.join(","));

    fetch(url, { credentials: "same-origin" })
      .then((response) => {
        if (!response.ok) throw new Error("HTTP " + response.status);
        return response.json();
      })
      .then((payload) => {
        if (!payload || !payload.ok || !payload.roles) throw new Error("Invalid role member response");
        targets.forEach((target) => {
          const roleId = target.dataset.roleId;
          const role = payload.roles[roleId];
          renderDynamicMembers(target, role ? role.members : []);
        });
      })
      .catch(() => {
        targets.forEach((target) => {
          target.textContent = "Role member list could not be loaded here.";
        });
      });
  }

  addIntro();
  addCoreTeam();
  addRoleSources();
  (data.departments || []).forEach(addDepartment);
  addSupporters();
  addBotSystem();
  addSimplePillSection("Database System", data.database);
  addParagraphSection("Special Thanks", data.specialThanks);
  addSimplePillSection("Powered by", data.poweredBy);

  const version = section("Version");
  version.appendChild(el("div", "person", data.version));
  root.appendChild(version);

  const legal = addParagraphSection("Legal", data.legal);
  legal.appendChild(el("div", "end-mark", data.endMark));

  loadDynamicRoleMembers();
})();
