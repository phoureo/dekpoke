(function () {
  "use strict";

  function deepClone(value) {
    return JSON.parse(JSON.stringify(value));
  }

  function clamp(value, min, max) {
    return Math.max(min, Math.min(max, value));
  }

  function id(prefix) {
    return `${prefix}_${Date.now().toString(36)}_${Math.random().toString(36).slice(2, 8)}`;
  }

  function escapeHtml(value) {
    return String(value ?? "").replace(/[&<>"']/g, (character) => ({
      "&": "&amp;",
      "<": "&lt;",
      ">": "&gt;",
      '"': "&quot;",
      "'": "&#039;"
    }[character]));
  }

  function hasDataUrl(value) {
    if (typeof value === "string") {
      return value.trim().toLowerCase().startsWith("data:");
    }
    if (Array.isArray(value)) {
      return value.some(hasDataUrl);
    }
    if (value && typeof value === "object") {
      return Object.values(value).some(hasDataUrl);
    }
    return false;
  }

  function toast(message, kind = "") {
    const old = document.querySelector(".editor-toast");
    if (old) old.remove();
    const node = document.createElement("div");
    node.className = `editor-toast${kind ? ` is-${kind}` : ""}`;
    node.textContent = String(message || "");
    document.body.appendChild(node);
    window.setTimeout(() => {
      node.remove();
    }, kind === "bad" ? 5200 : 2800);
  }

  function installTooltips(root = document) {
    let tooltip = document.querySelector(".editor-tooltip-portal");
    if (!tooltip) {
      tooltip = document.createElement("div");
      tooltip.className = "editor-tooltip-portal is-hidden";
      document.body.appendChild(tooltip);
    }
    let active = null;
    const show = (target, event) => {
      const text = target.getAttribute("data-tip") || target.getAttribute("data-tooltip") || target.getAttribute("aria-label") || "";
      if (!text) return;
      active = target;
      tooltip.textContent = text;
      tooltip.classList.remove("is-hidden");
      const rect = target.getBoundingClientRect();
      const left = clamp(rect.right + 10, 8, window.innerWidth - tooltip.offsetWidth - 8);
      const top = clamp(rect.top + rect.height / 2 - tooltip.offsetHeight / 2, 8, window.innerHeight - tooltip.offsetHeight - 8);
      tooltip.style.left = `${left}px`;
      tooltip.style.top = `${top}px`;
      if (event?.type === "mousemove") {
        tooltip.style.left = `${clamp(event.clientX + 14, 8, window.innerWidth - tooltip.offsetWidth - 8)}px`;
        tooltip.style.top = `${clamp(event.clientY + 14, 8, window.innerHeight - tooltip.offsetHeight - 8)}px`;
      }
    };
    const hide = () => {
      active = null;
      tooltip.classList.add("is-hidden");
    };
    root.addEventListener("mouseover", (event) => {
      const target = event.target.closest?.("[data-tip],[data-tooltip]");
      if (!target || !root.contains(target)) return;
      show(target, event);
    });
    root.addEventListener("mousemove", (event) => {
      if (!active) return;
      show(active, event);
    });
    root.addEventListener("mouseout", (event) => {
      if (!active) return;
      if (event.relatedTarget && active.contains(event.relatedTarget)) return;
      hide();
    });
    root.addEventListener("focusin", (event) => {
      const target = event.target.closest?.("[data-tip],[data-tooltip]");
      if (target) show(target);
    });
    root.addEventListener("focusout", hide);
  }

  function openContextMenu(items, x, y) {
    closeContextMenu();
    const menu = document.createElement("div");
    menu.className = "editor-context-menu";
    menu.setAttribute("role", "menu");
    menu.innerHTML = items
      .filter(Boolean)
      .map((item, index) => `
        <button type="button" role="menuitem" data-menu-index="${index}"${item.disabled ? " disabled" : ""}>
          ${item.icon ? `<i class="${escapeHtml(item.icon)}"></i>` : ""}
          <span>${escapeHtml(item.label || "")}</span>
        </button>
      `).join("");
    document.body.appendChild(menu);
    const left = clamp(x, 8, window.innerWidth - menu.offsetWidth - 8);
    const top = clamp(y, 8, window.innerHeight - menu.offsetHeight - 8);
    menu.style.left = `${left}px`;
    menu.style.top = `${top}px`;
    menu.addEventListener("click", (event) => {
      const button = event.target.closest("[data-menu-index]");
      if (!button || button.disabled) return;
      const item = items[Number(button.dataset.menuIndex)];
      closeContextMenu();
      if (typeof item?.action === "function") item.action();
    });
    const closer = (event) => {
      if (menu.contains(event.target)) return;
      closeContextMenu();
      document.removeEventListener("pointerdown", closer, true);
      document.removeEventListener("keydown", keyCloser, true);
    };
    const keyCloser = (event) => {
      if (event.key === "Escape") {
        closeContextMenu();
        document.removeEventListener("pointerdown", closer, true);
        document.removeEventListener("keydown", keyCloser, true);
      }
    };
    window.setTimeout(() => document.addEventListener("pointerdown", closer, true), 0);
    document.addEventListener("keydown", keyCloser, true);
    return menu;
  }

  function closeContextMenu() {
    document.querySelectorAll(".editor-context-menu").forEach((node) => node.remove());
  }

  function autoGridColumns(frameCount) {
    const count = Math.max(1, Math.round(Number(frameCount || 1)));
    let best = count;
    let bestScore = Infinity;
    for (let col = 1; col <= count; col += 1) {
      const rows = Math.ceil(count / col);
      const empty = (col * rows) - count;
      const score = Math.abs(col - rows) + empty * 2 + (col >= rows ? 0 : 0.5);
      if (score < bestScore) {
        best = col;
        bestScore = score;
      }
    }
    return best;
  }

  function throttleFrame(fn) {
    let frame = 0;
    let latestArgs = null;
    return (...args) => {
      latestArgs = args;
      if (frame) return;
      frame = window.requestAnimationFrame(() => {
        frame = 0;
        fn(...latestArgs);
      });
    };
  }

  async function copyText(value) {
    const text = String(value || "");
    if (!text) return false;
    if (navigator.clipboard?.writeText) {
      await navigator.clipboard.writeText(text);
      toast("คัดลอกแล้ว", "good");
      return true;
    }
    const input = document.createElement("textarea");
    input.value = text;
    input.style.position = "fixed";
    input.style.opacity = "0";
    document.body.append(input);
    input.select();
    const ok = document.execCommand("copy");
    input.remove();
    if (ok) toast("คัดลอกแล้ว", "good");
    return ok;
  }

  class HistoryStack {
    constructor(options = {}) {
      this.limit = Math.max(10, Number(options.limit || 80));
      this.undoStack = [];
      this.redoStack = [];
      this.onChange = typeof options.onChange === "function" ? options.onChange : () => {};
    }

    reset(snapshot) {
      this.undoStack = snapshot ? [deepClone(snapshot)] : [];
      this.redoStack = [];
      this.onChange(this);
    }

    push(snapshot) {
      if (!snapshot) return;
      const next = JSON.stringify(snapshot);
      const current = this.undoStack.length ? JSON.stringify(this.undoStack[this.undoStack.length - 1]) : "";
      if (next === current) return;
      this.undoStack.push(deepClone(snapshot));
      if (this.undoStack.length > this.limit) {
        this.undoStack.splice(0, this.undoStack.length - this.limit);
      }
      this.redoStack = [];
      this.onChange(this);
    }

    undo(currentSnapshot) {
      if (this.undoStack.length <= 1) return null;
      if (currentSnapshot) {
        this.redoStack.push(deepClone(currentSnapshot));
      } else {
        this.redoStack.push(this.undoStack[this.undoStack.length - 1]);
      }
      this.undoStack.pop();
      this.onChange(this);
      return deepClone(this.undoStack[this.undoStack.length - 1]);
    }

    redo(currentSnapshot) {
      if (!this.redoStack.length) return null;
      if (currentSnapshot) {
        this.undoStack.push(deepClone(currentSnapshot));
      }
      const snapshot = this.redoStack.pop();
      this.onChange(this);
      return deepClone(snapshot);
    }

    canUndo() {
      return this.undoStack.length > 1;
    }

    canRedo() {
      return this.redoStack.length > 0;
    }
  }

  async function uploadAsset(url, file, assetType, extra = {}) {
    const form = new FormData();
    form.append("action", "upload_asset");
    form.append("assetType", assetType);
    form.append("image", file);
    for (const [key, value] of Object.entries(extra)) {
      form.append(key, String(value));
    }
    const response = await fetch(url, {
      method: "POST",
      credentials: "same-origin",
      body: form
    });
    const data = await response.json().catch(() => null);
    if (!response.ok || !data || data.ok === false) {
      throw new Error(data?.message || data?.code || `HTTP ${response.status}`);
    }
    return data;
  }

  window.DekpokeEditor = {
    autoGridColumns,
    clamp,
    closeContextMenu,
    copyText,
    deepClone,
    escapeHtml,
    hasDataUrl,
    HistoryStack,
    id,
    installTooltips,
    openContextMenu,
    throttleFrame,
    toast,
    uploadAsset
  };
})();
