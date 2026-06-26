(function () {
  "use strict";

  const Editor = window.DekpokeEditor;
  const boot = window.ASSET_MANIFEST_EDITOR_BOOT || {};
  const apiUrl = new URL(String(boot.apiUrl || "asset-manifest-api.php"), window.location.href);
  const csrfToken = String(boot.csrfToken || "");

  const refs = {
    root: document.getElementById("assetManifestRoot"),
    manifestModePill: document.getElementById("manifestModePill"),
    saveAllowedPill: document.getElementById("saveAllowedPill"),
    gameVersionPill: document.getElementById("gameVersionPill"),
    contentVersionPill: document.getElementById("contentVersionPill"),
    refreshBootstrapButton: document.getElementById("refreshBootstrapButton"),
    saveDraftButton: document.getElementById("saveDraftButton"),
    publishButton: document.getElementById("publishButton"),
    pageFilterButtons: Array.from(document.querySelectorAll("[data-page-filter]")),
    addGroupButton: document.getElementById("addGroupButton"),
    createRuleButton: document.getElementById("createRuleButton"),
    duplicateRuleButton: document.getElementById("duplicateRuleButton"),
    removeRuleButton: document.getElementById("removeRuleButton"),
    assetSearchInput: document.getElementById("assetSearchInput"),
    groupFilterSelect: document.getElementById("groupFilterSelect"),
    assetCountLabel: document.getElementById("assetCountLabel"),
    assetList: document.getElementById("assetList"),
    assetPreview: document.getElementById("assetPreview"),
    assetInspector: document.getElementById("assetInspector"),
    selectedAssetBadge: document.getElementById("selectedAssetBadge"),
    gameVersionInput: document.getElementById("gameVersionInput"),
    contentVersionInput: document.getElementById("contentVersionInput"),
    manifestNotesInput: document.getElementById("manifestNotesInput"),
    groupList: document.getElementById("groupList"),
    versionList: document.getElementById("versionList"),
    jsonPreview: document.getElementById("jsonPreview"),
    managedCountLabel: document.getElementById("managedCountLabel"),
    preloadCountLabel: document.getElementById("preloadCountLabel"),
    groupCountLabel: document.getElementById("groupCountLabel"),
    versionCountLabel: document.getElementById("versionCountLabel"),
    selectionStatusLabel: document.getElementById("selectionStatusLabel")
  };

  const state = {
    pageDefinitions: [],
    liveManifest: null,
    manifest: null,
    versions: { versions: [] },
    assetInventory: [],
    saveAllowed: false,
    liveManifestExists: false,
    hasDraft: false,
    dirty: false,
    pageFilter: "all",
    groupFilter: "all",
    search: "",
    selectedPath: ""
  };

  function api(action, body = null) {
    const url = new URL(apiUrl.toString());
    url.searchParams.set("action", action);
    const headers = body ? {
      "Content-Type": "application/json",
      "X-CSRF-Token": csrfToken
    } : undefined;
    return fetch(url, {
      method: body ? "POST" : "GET",
      credentials: "same-origin",
      cache: "no-store",
      headers,
      body: body ? JSON.stringify(body) : null
    }).then(async (response) => {
      const data = await response.json().catch(() => null);
      if (!response.ok || !data || data.ok === false) {
        throw new Error(data?.message || data?.code || `HTTP ${response.status}`);
      }
      return data;
    });
  }

  function pageMap() {
    const map = new Map();
    state.pageDefinitions.forEach((page) => {
      const id = String(page?.id || "").trim();
      if (id) map.set(id, page);
    });
    return map;
  }

  function manifestAssets() {
    return Array.isArray(state.manifest?.assets) ? state.manifest.assets : [];
  }

  function manifestGroups() {
    return Array.isArray(state.manifest?.groups) ? state.manifest.groups : [];
  }

  function groupById(groupId) {
    return manifestGroups().find((group) => String(group?.id || "") === String(groupId || "")) || null;
  }

  function findRule(path) {
    return manifestAssets().find((asset) => String(asset?.path || "") === String(path || "")) || null;
  }

  function findRuleIndex(path) {
    return manifestAssets().findIndex((asset) => String(asset?.path || "") === String(path || ""));
  }

  function sortManifest() {
    if (!state.manifest) return;
    state.manifest.groups = manifestGroups()
      .slice()
      .sort((a, b) => (Number(a?.order || 0) - Number(b?.order || 0)) || String(a?.label || "").localeCompare(String(b?.label || "")));
    state.manifest.assets = manifestAssets()
      .slice()
      .sort((a, b) => String(a?.path || "").localeCompare(String(b?.path || "")));
  }

  function manifestTouched() {
    sortManifest();
    state.dirty = true;
    renderAll();
  }

  function inventoryItem(path) {
    return state.assetInventory.find((asset) => String(asset?.path || "") === String(path || "")) || null;
  }

  function fileName(path) {
    return String(path || "").split("/").filter(Boolean).pop() || String(path || "");
  }

  function togglePill(node, mode) {
    node.classList.toggle("is-good", mode === "good");
    node.classList.toggle("is-warn", mode === "warn");
    node.classList.toggle("is-bad", mode === "bad");
  }

  function selectedRule() {
    return findRule(state.selectedPath);
  }

  function selectedItem() {
    return inventoryItem(state.selectedPath) || (state.selectedPath ? { path: state.selectedPath, name: fileName(state.selectedPath) } : null);
  }

  function ensureSelectedPath() {
    if (state.selectedPath && (inventoryItem(state.selectedPath) || findRule(state.selectedPath))) {
      return;
    }
    const first = filteredAssets()[0];
    state.selectedPath = first?.path || "";
  }

  function matchesPage(rule, pageId) {
    const pages = Array.isArray(rule?.pages) ? rule.pages : [];
    if (!pages.length) return true;
    return pages.includes(pageId);
  }

  function filteredAssets() {
    const map = new Map();
    state.assetInventory.forEach((asset) => {
      const path = String(asset?.path || "");
      if (!path) return;
      map.set(path, {
        ...asset,
        path,
        name: asset?.name || fileName(path)
      });
    });
    manifestAssets().forEach((rule) => {
      const path = String(rule?.path || "");
      if (!path || map.has(path)) return;
      map.set(path, {
        path,
        name: fileName(path),
        width: 0,
        height: 0,
        mime: "",
        updatedAt: "",
        missing: true
      });
    });

    const search = state.search.trim().toLowerCase();
    return Array.from(map.values())
      .filter((asset) => {
        const rule = findRule(asset.path);
        if (state.pageFilter !== "all" && rule && !matchesPage(rule, state.pageFilter)) {
          return false;
        }
        if (state.groupFilter !== "all") {
          const groupId = String(rule?.groupId || "");
          if (state.groupFilter === "__unmanaged__") {
            if (rule) return false;
          } else if (groupId !== state.groupFilter) {
            return false;
          }
        }
        if (!search) return true;
        return String(asset.path).toLowerCase().includes(search) || String(asset.name || "").toLowerCase().includes(search);
      })
      .sort((a, b) => {
        const managedDelta = Number(Boolean(findRule(b.path))) - Number(Boolean(findRule(a.path)));
        if (managedDelta !== 0) return managedDelta;
        return String(a.path).localeCompare(String(b.path));
      });
  }

  function resolvedVersion(rule) {
    if (!rule) return "";
    const mode = String(rule.versionMode || "none");
    if (mode === "custom") {
      return String(rule.customVersion || "").trim();
    }
    if (mode === "inherit-content-version") {
      return String(state.manifest?.meta?.contentVersion || "").trim();
    }
    return "";
  }

  function resolvedUrl(path, rule) {
    if (!path) return "";
    const version = resolvedVersion(rule);
    if (!version) return path;
    const joiner = String(path).includes("?") ? "&" : "?";
    return `${path}${joiner}assetv=${encodeURIComponent(version)}`;
  }

  function previewAssetCard(path, rule) {
    const item = inventoryItem(path);
    const url = resolvedUrl(path, rule);
    const meta = [];
    if (item?.width && item?.height) meta.push(`${item.width}x${item.height}`);
    if (item?.mime) meta.push(item.mime);
    if (rule) meta.push(rule.cachePolicy === "no-store" ? "no-store" : "cache default");
    return `
      <div class="editor-asset-preview-card">
        <div class="editor-asset-preview-media">
          <img src="${Editor.escapeHtml(url)}" alt="${Editor.escapeHtml(fileName(path))}" loading="lazy">
        </div>
        <div class="editor-asset-preview-copy">
          <strong>${Editor.escapeHtml(fileName(path))}</strong>
          <div class="editor-asset-meta">${Editor.escapeHtml(meta.join(" • ") || "ยังไม่มี metadata")}</div>
          <div class="editor-asset-path">${Editor.escapeHtml(path)}</div>
          <div class="editor-asset-path">${Editor.escapeHtml(url)}</div>
        </div>
      </div>
    `;
  }

  function groupOptions(selected = "") {
    return [
      `<option value="">ไม่จัด group</option>`,
      ...manifestGroups().map((group) => (
        `<option value="${Editor.escapeHtml(group.id)}"${String(group.id) === String(selected) ? " selected" : ""}>${Editor.escapeHtml(group.label || group.id)}</option>`
      ))
    ].join("");
  }

  function pageCheckboxes(selectedPages = []) {
    const pageIds = Array.isArray(selectedPages) ? selectedPages : [];
    return state.pageDefinitions.map((page) => `
      <label class="asset-manifest-check">
        <input type="checkbox" data-rule-field="page" value="${Editor.escapeHtml(page.id)}"${pageIds.includes(page.id) ? " checked" : ""}>
        <span>${Editor.escapeHtml(page.label || page.id)}</span>
      </label>
    `).join("");
  }

  function renderAssetInspector() {
    const path = state.selectedPath;
    const item = selectedItem();
    const rule = selectedRule();

    refs.selectedAssetBadge.textContent = item ? fileName(item.path) : "ยังไม่เลือก";
    refs.selectionStatusLabel.textContent = item ? `เลือก ${item.path}` : "ยังไม่เลือกไฟล์";
    refs.assetPreview.innerHTML = item
      ? previewAssetCard(item.path, rule)
      : `<div class="editor-note">เลือกไฟล์จากด้านซ้ายเพื่อดูตัวอย่างและตั้งค่ากฎ cache / preload / version</div>`;

    if (!item) {
      refs.assetInspector.innerHTML = `<div class="editor-note">ยังไม่มีไฟล์ที่เลือก</div>`;
      return;
    }

    if (!rule) {
      refs.assetInspector.innerHTML = `
        <div class="editor-note">ไฟล์นี้ยังไม่ถูกคุมด้วย manifest</div>
        <div class="editor-actions">
          <button class="ui tiny primary button" type="button" data-action="create-rule-selected"><i class="fa-solid fa-plus"></i> เพิ่มกฎให้ไฟล์นี้</button>
          <button class="ui tiny button" type="button" data-action="copy-path"><i class="fa-regular fa-copy"></i> คัดลอก path</button>
        </div>
      `;
      return;
    }

    const group = groupById(rule.groupId);
    const resolved = resolvedUrl(path, rule);
    refs.assetInspector.innerHTML = `
      <div class="editor-grid-2">
        <label class="editor-field">Path
          <input type="text" value="${Editor.escapeHtml(rule.path)}" readonly>
        </label>
        <label class="editor-field">Group
          <select data-rule-field="groupId">
            ${groupOptions(rule.groupId)}
          </select>
        </label>
      </div>
      <label class="editor-field">ใช้งานบนหน้า
        <div class="asset-manifest-check-grid">${pageCheckboxes(rule.pages)}</div>
      </label>
      <div class="editor-grid-3">
        <label class="editor-field">Cache Policy
          <select data-rule-field="cachePolicy">
            <option value="default"${rule.cachePolicy === "default" ? " selected" : ""}>default</option>
            <option value="no-store"${rule.cachePolicy === "no-store" ? " selected" : ""}>no-store</option>
          </select>
        </label>
        <label class="editor-field">Version Mode
          <select data-rule-field="versionMode">
            <option value="none"${rule.versionMode === "none" ? " selected" : ""}>none</option>
            <option value="inherit-content-version"${rule.versionMode === "inherit-content-version" ? " selected" : ""}>inherit-content-version</option>
            <option value="custom"${rule.versionMode === "custom" ? " selected" : ""}>custom</option>
          </select>
        </label>
        <label class="editor-field">Mime Type
          <select data-rule-field="mimeType">
            <option value=""${!rule.mimeType ? " selected" : ""}>default</option>
            <option value="auto"${rule.mimeType === "auto" ? " selected" : ""}>auto</option>
            <option value="image/png"${rule.mimeType === "image/png" ? " selected" : ""}>image/png</option>
            <option value="image/jpeg"${rule.mimeType === "image/jpeg" ? " selected" : ""}>image/jpeg</option>
            <option value="image/webp"${rule.mimeType === "image/webp" ? " selected" : ""}>image/webp</option>
            <option value="image/gif"${rule.mimeType === "image/gif" ? " selected" : ""}>image/gif</option>
          </select>
        </label>
      </div>
      <div class="editor-grid-2">
        <label class="editor-field">Custom Version
          <input type="text" data-rule-field="customVersion" value="${Editor.escapeHtml(rule.customVersion || "")}"${rule.versionMode === "custom" ? "" : " disabled"}>
        </label>
        <label class="editor-field">Resolved URL
          <input type="text" value="${Editor.escapeHtml(resolved)}" readonly>
        </label>
      </div>
      <div class="asset-manifest-check-grid is-inline">
        <label class="asset-manifest-check">
          <input type="checkbox" data-rule-field="enabled"${rule.enabled !== false ? " checked" : ""}>
          <span>เปิดใช้กฎนี้</span>
        </label>
        <label class="asset-manifest-check">
          <input type="checkbox" data-rule-field="preload"${rule.preload !== false ? " checked" : ""}>
          <span>ให้ preload ตอนเปิดหน้า</span>
        </label>
      </div>
      <label class="editor-field">Notes
        <textarea data-rule-field="notes" class="editor-textarea">${Editor.escapeHtml(rule.notes || "")}</textarea>
      </label>
      <div class="editor-actions">
        <button class="ui tiny button" type="button" data-action="copy-path"><i class="fa-regular fa-copy"></i> คัดลอก path</button>
        <button class="ui tiny button" type="button" data-action="copy-url"><i class="fa-regular fa-copy"></i> คัดลอก resolved URL</button>
        <button class="ui tiny button" type="button" data-action="duplicate-rule"><i class="fa-regular fa-copy"></i> คัดลอกกฎ</button>
        <button class="ui tiny negative button" type="button" data-action="remove-rule"><i class="fa-solid fa-trash"></i> ลบกฎ</button>
      </div>
      <div class="editor-note">
        ${group ? `group นี้จะใช้ข้อความ preload ว่า "${Editor.escapeHtml(group.preloadLabel || group.label || group.id)}"` : "ถ้าไม่จัด group ระบบจะใช้ label กลางของหน้า"}
      </div>
    `;
  }

  function renderAssetList() {
    const rows = filteredAssets();
    ensureSelectedPath();
    refs.assetCountLabel.textContent = String(rows.length);
    refs.assetList.innerHTML = rows.length ? rows.map((asset) => {
      const rule = findRule(asset.path);
      const group = groupById(rule?.groupId || "");
      const status = rule
        ? [
            rule.enabled === false ? "disabled" : "managed",
            rule.preload === false ? "no preload" : "preload",
            rule.cachePolicy === "no-store" ? "no-store" : "cache"
          ].join(" • ")
        : "legacy fallback";
      return `
        <button class="editor-list-row asset-manifest-row${asset.path === state.selectedPath ? " is-active" : ""}" type="button" data-asset-path="${Editor.escapeHtml(asset.path)}">
          <span class="asset-manifest-copy">
            <strong>${Editor.escapeHtml(fileName(asset.path))}</strong>
            <span class="editor-asset-path">${Editor.escapeHtml(asset.path)}</span>
            <span class="editor-asset-meta">${Editor.escapeHtml(status)}</span>
          </span>
          <span class="asset-manifest-tags">
            ${group ? `<span class="asset-manifest-tag">${Editor.escapeHtml(group.label || group.id)}</span>` : ""}
            ${rule?.versionMode && rule.versionMode !== "none" ? `<span class="asset-manifest-tag is-accent">${Editor.escapeHtml(rule.versionMode)}</span>` : ""}
          </span>
        </button>
      `;
    }).join("") : `<div class="editor-note">ไม่พบไฟล์ตามเงื่อนไขที่กรองไว้</div>`;
  }

  function renderGroupFilter() {
    refs.groupFilterSelect.innerHTML = [
      `<option value="all">ทุก group</option>`,
      `<option value="__unmanaged__">ยังไม่มีกฎ</option>`,
      ...manifestGroups().map((group) => (
        `<option value="${Editor.escapeHtml(group.id)}"${state.groupFilter === group.id ? " selected" : ""}>${Editor.escapeHtml(group.label || group.id)}</option>`
      ))
    ].join("");
  }

  function renderGroupList() {
    refs.groupList.innerHTML = manifestGroups().length ? manifestGroups().map((group) => {
      const usage = manifestAssets().filter((asset) => String(asset?.groupId || "") === String(group.id)).length;
      const pages = Array.isArray(group.pages) && group.pages.length ? group.pages.join(", ") : "all";
      return `
        <div class="editor-list-row">
          <span class="asset-manifest-copy">
            <strong>${Editor.escapeHtml(group.label || group.id)}</strong>
            <span class="editor-asset-path">${Editor.escapeHtml(group.id)}</span>
            <span class="editor-asset-meta">${Editor.escapeHtml(`${usage} assets • ${pages}`)}</span>
          </span>
          <span class="asset-manifest-tags">
            <button class="ui mini icon button" type="button" data-group-action="edit" data-group-id="${Editor.escapeHtml(group.id)}"><i class="fa-solid fa-pen"></i></button>
            <button class="ui mini icon button" type="button" data-group-action="delete" data-group-id="${Editor.escapeHtml(group.id)}"><i class="fa-solid fa-trash"></i></button>
          </span>
        </div>
      `;
    }).join("") : `<div class="editor-note">ยังไม่มีกลุ่ม</div>`;
  }

  function renderVersions() {
    const versions = Array.isArray(state.versions?.versions) ? state.versions.versions : [];
    refs.versionList.innerHTML = versions.length ? versions.map((version) => `
      <div class="editor-list-row asset-manifest-version-row">
        <span class="asset-manifest-copy">
          <strong>${Editor.escapeHtml(version.contentVersion || version.id || "-")}</strong>
          <span class="editor-asset-path">${Editor.escapeHtml(version.gameVersion || "")}</span>
          <span class="editor-asset-meta">${Editor.escapeHtml(`${version.assetCount || 0} assets • ${version.groupCount || 0} groups • ${version.createdAt || ""}`)}</span>
        </span>
        <span class="asset-manifest-tags">
          <button class="ui mini button" type="button" data-version-action="draft" data-version-id="${Editor.escapeHtml(version.id)}">ดึงเป็น draft</button>
          <button class="ui mini button" type="button" data-version-action="publish" data-version-id="${Editor.escapeHtml(version.id)}">ย้อนและเผยแพร่</button>
        </span>
      </div>
    `).join("") : `<div class="editor-note">ยังไม่มี version ที่บันทึกไว้</div>`;
  }

  function renderMetaPanel() {
    const meta = state.manifest?.meta || {};
    refs.gameVersionInput.value = String(meta.gameVersion || "");
    refs.contentVersionInput.value = String(meta.contentVersion || "");
    refs.manifestNotesInput.value = String(meta.notes || "");
    refs.gameVersionPill.textContent = `Game v${meta.gameVersion || "-"}`;
    refs.contentVersionPill.textContent = `Content ${meta.contentVersion || "-"}`;
  }

  function renderPills() {
    refs.manifestModePill.textContent = state.dirty
      ? "มีการแก้ไขยังไม่บันทึก"
      : (state.hasDraft ? "มี draft ค้างอยู่" : (state.liveManifestExists ? "ใช้ live manifest" : "ใช้ generated defaults"));
    togglePill(refs.manifestModePill, state.dirty ? "warn" : (state.hasDraft ? "warn" : (state.liveManifestExists ? "good" : "warn")));
    refs.saveAllowedPill.textContent = state.saveAllowed ? "แก้ไขได้" : "อ่านอย่างเดียว";
    togglePill(refs.saveAllowedPill, state.saveAllowed ? "good" : "bad");
  }

  function renderCounts() {
    const managed = manifestAssets().length;
    const preload = manifestAssets().filter((asset) => asset?.enabled !== false && asset?.preload !== false).length;
    const versions = Array.isArray(state.versions?.versions) ? state.versions.versions.length : 0;
    refs.managedCountLabel.textContent = String(managed);
    refs.preloadCountLabel.textContent = String(preload);
    refs.groupCountLabel.textContent = String(manifestGroups().length);
    refs.versionCountLabel.textContent = String(versions);
  }

  function renderJson() {
    refs.jsonPreview.value = JSON.stringify(state.manifest || {}, null, 2);
  }

  function renderPageFilters() {
    refs.pageFilterButtons.forEach((button) => {
      button.classList.toggle("is-active", button.dataset.pageFilter === state.pageFilter);
    });
  }

  function renderAll() {
    renderPills();
    renderMetaPanel();
    renderPageFilters();
    renderGroupFilter();
    renderAssetList();
    renderAssetInspector();
    renderGroupList();
    renderVersions();
    renderCounts();
    renderJson();
  }

  function seedState(data) {
    state.pageDefinitions = Array.isArray(data?.pageDefinitions) ? data.pageDefinitions : [];
    state.liveManifest = data?.liveManifest || null;
    state.manifest = Editor.deepClone(data?.workingManifest || data?.liveManifest || { version: 1, meta: {}, groups: [], assets: [] });
    state.versions = data?.versions || { versions: [] };
    state.assetInventory = Array.isArray(data?.assets?.assets) ? data.assets.assets : [];
    state.saveAllowed = Boolean(data?.saveAllowed);
    state.liveManifestExists = Boolean(data?.liveManifestExists);
    state.hasDraft = Boolean(data?.hasDraft);
    state.dirty = false;
    if (!state.groupFilter) state.groupFilter = "all";
    ensureSelectedPath();
    renderAll();
  }

  async function refreshBootstrap() {
    refs.refreshBootstrapButton.disabled = true;
    try {
      const data = await api("bootstrap");
      seedState(data);
      Editor.toast("โหลด asset manifest แล้ว", "good");
    } catch (error) {
      Editor.toast(error.message || "โหลด asset manifest ไม่สำเร็จ", "bad");
    } finally {
      refs.refreshBootstrapButton.disabled = false;
    }
  }

  function createDefaultRule(path) {
    return {
      path,
      pages: state.pageFilter !== "all" ? [state.pageFilter] : [],
      groupId: state.groupFilter !== "all" && state.groupFilter !== "__unmanaged__" ? state.groupFilter : "",
      cachePolicy: "default",
      versionMode: "none",
      customVersion: "",
      mimeType: "",
      enabled: true,
      preload: true,
      notes: ""
    };
  }

  function addRuleForPath(path) {
    const targetPath = String(path || state.selectedPath || "").trim();
    if (!targetPath) {
      Editor.toast("ยังไม่ได้เลือกไฟล์", "bad");
      return;
    }
    if (findRule(targetPath)) {
      Editor.toast("ไฟล์นี้มีกฎอยู่แล้ว", "warn");
      return;
    }
    state.manifest.assets.push(createDefaultRule(targetPath));
    state.selectedPath = targetPath;
    manifestTouched();
  }

  function duplicateRule(path) {
    const source = findRule(path || state.selectedPath);
    if (!source) {
      Editor.toast("ไม่มีกฎให้คัดลอก", "bad");
      return;
    }
    const nextPath = window.prompt("คัดลอกกฎไปยัง path ไหน", String(source.path || ""));
    if (!nextPath) return;
    const trimmed = String(nextPath).trim();
    if (!trimmed) return;
    const clone = Editor.deepClone(source);
    clone.path = trimmed;
    const index = findRuleIndex(trimmed);
    if (index >= 0) {
      state.manifest.assets[index] = clone;
    } else {
      state.manifest.assets.push(clone);
    }
    state.selectedPath = trimmed;
    manifestTouched();
  }

  function removeRule(path) {
    const targetPath = String(path || state.selectedPath || "");
    const index = findRuleIndex(targetPath);
    if (index < 0) {
      Editor.toast("ไม่มีกฎให้ลบ", "bad");
      return;
    }
    state.manifest.assets.splice(index, 1);
    manifestTouched();
  }

  function saveMetaFromInputs() {
    if (!state.manifest?.meta) return;
    state.manifest.meta.gameVersion = String(refs.gameVersionInput.value || "").trim();
    state.manifest.meta.contentVersion = String(refs.contentVersionInput.value || "").trim();
    state.manifest.meta.notes = String(refs.manifestNotesInput.value || "");
    manifestTouched();
  }

  async function saveDraft() {
    if (!state.saveAllowed) {
      Editor.toast("บัญชีนี้ไม่มีสิทธิ์บันทึก draft", "bad");
      return;
    }
    refs.saveDraftButton.disabled = true;
    try {
      const data = await api("save_draft", { manifest: state.manifest });
      Editor.toast("บันทึกร่างแล้ว", "good");
      state.hasDraft = true;
      state.versions = data.versions || state.versions;
      state.manifest = Editor.deepClone(data.manifest || state.manifest);
      state.dirty = false;
      renderAll();
    } catch (error) {
      Editor.toast(error.message || "บันทึกร่างไม่สำเร็จ", "bad");
    } finally {
      refs.saveDraftButton.disabled = false;
    }
  }

  async function publishDraft() {
    if (!state.saveAllowed) {
      Editor.toast("บัญชีนี้ไม่มีสิทธิ์เผยแพร่", "bad");
      return;
    }
    if (!window.confirm("เผยแพร่ manifest นี้ขึ้น live และเก็บ version เดิมไว้ก่อน ใช่หรือไม่")) {
      return;
    }
    refs.publishButton.disabled = true;
    try {
      await api("publish_draft", { manifest: state.manifest });
      Editor.toast("เผยแพร่ asset manifest แล้ว", "good");
      await refreshBootstrap();
    } catch (error) {
      Editor.toast(error.message || "เผยแพร่ไม่สำเร็จ", "bad");
    } finally {
      refs.publishButton.disabled = false;
    }
  }

  async function rollbackVersion(versionId, publish) {
    if (!state.saveAllowed) {
      Editor.toast("บัญชีนี้ไม่มีสิทธิ์ rollback", "bad");
      return;
    }
    const confirmed = publish
      ? window.confirm("จะย้อน version นี้และเผยแพร่ขึ้น live ทันที ใช่หรือไม่")
      : window.confirm("จะดึง version นี้มาเป็น draft ใช่หรือไม่");
    if (!confirmed) return;
    try {
      await api("rollback_version", {
        versionId,
        publish
      });
      Editor.toast(publish ? "ย้อน version และเผยแพร่แล้ว" : "ดึง version มาเป็น draft แล้ว", "good");
      await refreshBootstrap();
    } catch (error) {
      Editor.toast(error.message || "rollback ไม่สำเร็จ", "bad");
    }
  }

  function addGroup() {
    const id = String(window.prompt("รหัส group ใหม่", "") || "").trim().toLowerCase();
    if (!id) return;
    if (groupById(id)) {
      Editor.toast("มี group id นี้อยู่แล้ว", "bad");
      return;
    }
    const label = String(window.prompt("ชื่อ group", id) || "").trim() || id;
    state.manifest.groups.push({
      id,
      label,
      pages: state.pageFilter !== "all" ? [state.pageFilter] : [],
      preloadLabel: `Loading ${label}`,
      order: (manifestGroups().length + 1) * 10
    });
    manifestTouched();
  }

  function editGroup(groupId) {
    const group = groupById(groupId);
    if (!group) return;
    const label = String(window.prompt("ชื่อ group", String(group.label || group.id)) || "").trim();
    if (!label) return;
    const preloadLabel = String(window.prompt("ข้อความตอน preload", String(group.preloadLabel || `Loading ${label}`)) || "").trim();
    const pages = window.prompt("หน้าที่ใช้ group นี้ คั่นด้วย comma เช่น gacha-index,mileage-runtime", String((group.pages || []).join(",")));
    group.label = label;
    group.preloadLabel = preloadLabel || `Loading ${label}`;
    group.pages = String(pages || "")
      .split(",")
      .map((value) => value.trim())
      .filter(Boolean)
      .filter((value) => pageMap().has(value));
    manifestTouched();
  }

  function deleteGroup(groupId) {
    if (!window.confirm(`ลบ group ${groupId} ใช่หรือไม่`)) return;
    state.manifest.groups = manifestGroups().filter((group) => String(group?.id || "") !== String(groupId));
    manifestAssets().forEach((asset) => {
      if (String(asset?.groupId || "") === String(groupId)) {
        asset.groupId = "";
      }
    });
    if (state.groupFilter === groupId) {
      state.groupFilter = "all";
    }
    manifestTouched();
  }

  function bindEvents() {
    refs.refreshBootstrapButton.addEventListener("click", refreshBootstrap);
    refs.saveDraftButton.addEventListener("click", saveDraft);
    refs.publishButton.addEventListener("click", publishDraft);
    refs.addGroupButton.addEventListener("click", addGroup);
    refs.createRuleButton.addEventListener("click", () => addRuleForPath(state.selectedPath));
    refs.duplicateRuleButton.addEventListener("click", () => duplicateRule(state.selectedPath));
    refs.removeRuleButton.addEventListener("click", () => removeRule(state.selectedPath));

    refs.pageFilterButtons.forEach((button) => {
      button.addEventListener("click", () => {
        state.pageFilter = String(button.dataset.pageFilter || "all");
        ensureSelectedPath();
        renderAll();
      });
    });

    refs.assetSearchInput.addEventListener("input", () => {
      state.search = String(refs.assetSearchInput.value || "");
      ensureSelectedPath();
      renderAssetList();
      renderAssetInspector();
    });

    refs.groupFilterSelect.addEventListener("change", () => {
      state.groupFilter = String(refs.groupFilterSelect.value || "all");
      ensureSelectedPath();
      renderAssetList();
      renderAssetInspector();
    });

    [refs.gameVersionInput, refs.contentVersionInput, refs.manifestNotesInput].forEach((input) => {
      input.addEventListener("change", saveMetaFromInputs);
      input.addEventListener("blur", saveMetaFromInputs);
    });

    refs.assetList.addEventListener("click", (event) => {
      const button = event.target.closest("[data-asset-path]");
      if (!button) return;
      state.selectedPath = String(button.dataset.assetPath || "");
      renderAssetList();
      renderAssetInspector();
    });

    refs.assetInspector.addEventListener("click", async (event) => {
      const actionNode = event.target.closest("[data-action]");
      if (!actionNode) return;
      const action = String(actionNode.dataset.action || "");
      if (action === "create-rule-selected") {
        addRuleForPath(state.selectedPath);
      } else if (action === "copy-path") {
        await Editor.copyText(String(state.selectedPath || ""));
      } else if (action === "copy-url") {
        await Editor.copyText(resolvedUrl(state.selectedPath, selectedRule()));
      } else if (action === "duplicate-rule") {
        duplicateRule(state.selectedPath);
      } else if (action === "remove-rule") {
        removeRule(state.selectedPath);
      }
    });

    refs.assetInspector.addEventListener("change", (event) => {
      const field = event.target.getAttribute("data-rule-field");
      const rule = selectedRule();
      if (!field || !rule) return;

      if (field === "page") {
        const checked = Array.from(refs.assetInspector.querySelectorAll('[data-rule-field="page"]:checked'))
          .map((input) => String(input.value || ""))
          .filter(Boolean);
        rule.pages = checked;
      } else if (field === "enabled" || field === "preload") {
        rule[field] = Boolean(event.target.checked);
      } else {
        rule[field] = String(event.target.value || "");
      }

      manifestTouched();
    });

    refs.groupList.addEventListener("click", (event) => {
      const button = event.target.closest("[data-group-action]");
      if (!button) return;
      const action = String(button.dataset.groupAction || "");
      const groupId = String(button.dataset.groupId || "");
      if (action === "edit") {
        editGroup(groupId);
      } else if (action === "delete") {
        deleteGroup(groupId);
      }
    });

    refs.versionList.addEventListener("click", (event) => {
      const button = event.target.closest("[data-version-action]");
      if (!button) return;
      const versionId = String(button.dataset.versionId || "");
      const action = String(button.dataset.versionAction || "");
      rollbackVersion(versionId, action === "publish");
    });
  }

  function init() {
    Editor.installTooltips(document);
    bindEvents();
    refreshBootstrap();
  }

  init();
})();
