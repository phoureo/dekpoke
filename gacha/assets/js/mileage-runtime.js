(function () {
  "use strict";

  const DEFAULT_FX = {
    pathGlow: 1,
    pathLine: 1,
    clouds: 1,
    ambience: 1,
    friendCount: 3,
    selfPulse: 1
  };

  const LAYER_SLOT = {
    BACK: "decor-back",
    FRONT: "decor-front"
  };

  function clamp(value, min, max) {
    return Math.max(min, Math.min(max, Number.isFinite(Number(value)) ? Number(value) : min));
  }

  function lerp(a, b, t) {
    return a + (b - a) * t;
  }

  function boardSegments(board) {
    return Array.isArray(board?.image?.segments) ? board.image.segments : [];
  }

  function boardSize(board) {
    const segments = boardSegments(board);
    const height = Math.max(1, segments.reduce((sum, segment) => sum + Math.max(1, Number(segment?.h || 1)), 0) || Number(board?.image?.height || 1));
    return {
      width: Math.max(1, Number(board?.image?.width || 1)),
      height
    };
  }

  function segmentById(board, segmentId) {
    const id = String(segmentId || "");
    return boardSegments(board).find((segment) => String(segment?.id || "") === id) || boardSegments(board)[0] || null;
  }

  function pointFromLocation(board, location) {
    if (!board || !location) return null;
    const size = boardSize(board);
    if (typeof location.localX === "number" && typeof location.localY === "number" && location.segmentId) {
      const segment = segmentById(board, location.segmentId);
      if (segment) {
        return {
          x: clamp(location.localX, 0, 1) * size.width,
          y: Number(segment.y || 0) + clamp(location.localY, 0, 1) * Math.max(1, Number(segment.h || 1))
        };
      }
    }
    if (typeof location.x === "number" && typeof location.y === "number") {
      return {
        x: clamp(location.x, 0, 1) * size.width,
        y: clamp(location.y, 0, 1) * size.height
      };
    }
    return null;
  }

  function stepPoint(board, index) {
    return pointFromLocation(board, board?.steps?.[index] || null);
  }

  function entryPoint(board) {
    return pointFromLocation(board, board?.entry || null) || stepPoint(board, 0) || { x: boardSize(board).width * 0.5, y: boardSize(board).height * 0.92 };
  }

  function spriteFrameIndex(sprite, ts) {
    const count = Math.max(1, Number(sprite?.frameCount || 1));
    const fps = clamp(Number(sprite?.fps || 12), 1, 60);
    const frame = Math.floor((Number(ts || 0) / 1000) * fps);
    const mode = String(sprite?.mode || "loop");
    if (mode === "static") return clamp(Math.round(Number(sprite?.frameIndex || 0)), 0, count - 1);
    if (mode === "once") return Math.min(count - 1, frame);
    if (mode === "pingpong" && count > 1) {
      const cycle = count * 2 - 2;
      const p = frame % cycle;
      return p < count ? p : cycle - p;
    }
    return frame % count;
  }

  function spriteBox(sprite, image, frame) {
    const columns = Math.max(1, Number(sprite?.columns || 1));
    const rows = Math.max(1, Number(sprite?.rows || 1));
    const sw = Math.max(1, Number(sprite?.frameWidth || 0) || Math.floor((image?.naturalWidth || image?.width || 1) / columns));
    const sh = Math.max(1, Number(sprite?.frameHeight || 0) || Math.floor((image?.naturalHeight || image?.height || 1) / rows));
    const col = frame % columns;
    const row = Math.floor(frame / columns) % rows;
    return {
      sx: Math.min(Math.max(0, (image?.naturalWidth || image?.width || sw) - sw), (col * sw) + Number(sprite?.frameX || 0)),
      sy: Math.min(Math.max(0, (image?.naturalHeight || image?.height || sh) - sh), (row * sh) + Number(sprite?.frameY || 0)),
      sw,
      sh
    };
  }

  function rewardTone(kind) {
    return {
      coin: "#ffd581",
      ticket: "#9ae9ff",
      gem: "#ffd2ff",
      potion: "#9cffbf",
      item: "#b9a0ff"
    }[String(kind || "")] || "#ffd581";
  }

  function normalizeFx(board) {
    return { ...DEFAULT_FX, ...(board?.meta?.fx && typeof board.meta.fx === "object" ? board.meta.fx : {}) };
  }

  function imagePath(src) {
    return String(src || "").trim();
  }

  class MileageRuntime {
    constructor(canvas, options = {}) {
      this.canvas = canvas;
      this.ctx = canvas?.getContext?.("2d") || null;
      this.board = options.board || null;
      this.camera = { x: 0, y: 0, scale: 1 };
      this.imageCache = new Map();
      this.running = false;
      this.frameHandle = 0;
      this.simulation = {
        step: -1,
        walkValue: -1,
        friends: [],
        claimedRewardIds: new Set(),
        recentRewardIds: new Set(),
        showFriends: true,
        debug: false
      };
      this.onAfterRender = typeof options.onAfterRender === "function" ? options.onAfterRender : null;
    }

    setBoard(board) {
      this.board = board || null;
      this.preload();
      this.fit();
    }

    setCamera(camera) {
      this.camera = {
        x: Number(camera?.x || 0),
        y: Number(camera?.y || 0),
        scale: Math.max(0.01, Number(camera?.scale || 1))
      };
    }

    setSimulation(simulation = {}) {
      const claimed = simulation.claimedRewardIds instanceof Set
        ? simulation.claimedRewardIds
        : new Set(Array.isArray(simulation.claimedRewardIds) ? simulation.claimedRewardIds : []);
      const recent = simulation.recentRewardIds instanceof Set
        ? simulation.recentRewardIds
        : new Set(Array.isArray(simulation.recentRewardIds) ? simulation.recentRewardIds : []);
      this.simulation = {
        ...this.simulation,
        ...simulation,
        claimedRewardIds: claimed,
        recentRewardIds: recent
      };
    }

    start() {
      if (this.running) return;
      this.running = true;
      const tick = (ts) => {
        if (!this.running) return;
        this.render(ts);
        this.frameHandle = window.requestAnimationFrame(tick);
      };
      this.frameHandle = window.requestAnimationFrame(tick);
    }

    stop() {
      this.running = false;
      if (this.frameHandle) window.cancelAnimationFrame(this.frameHandle);
      this.frameHandle = 0;
    }

    resize() {
      if (!this.canvas) return;
      const rect = this.canvas.getBoundingClientRect();
      const dpr = clamp(window.devicePixelRatio || 1, 1, 1.5);
      const width = Math.max(1, Math.round((rect.width || 1) * dpr));
      const height = Math.max(1, Math.round((rect.height || 1) * dpr));
      if (this.canvas.width !== width || this.canvas.height !== height) {
        this.canvas.width = width;
        this.canvas.height = height;
      }
      this.dpr = dpr;
      this.viewportWidth = rect.width || width;
      this.viewportHeight = rect.height || height;
    }

    fit() {
      if (!this.canvas || !this.board) return;
      this.resize();
      const size = boardSize(this.board);
      const padding = 34;
      const sx = (this.viewportWidth - padding * 2) / size.width;
      const sy = (this.viewportHeight - padding * 2) / size.height;
      const scale = clamp(Math.min(sx, sy), 0.035, 2.5);
      this.camera = {
        scale,
        x: (this.viewportWidth - size.width * scale) / 2,
        y: (this.viewportHeight - size.height * scale) / 2
      };
    }

    focusStep(stepIndex, options = {}) {
      if (!this.board) return;
      const point = stepIndex >= 0 ? stepPoint(this.board, stepIndex) : entryPoint(this.board);
      if (!point) return;
      this.resize();
      const scale = clamp(Number(options.scale || this.camera.scale || 1), 0.035, 2.5);
      this.camera.scale = scale;
      this.camera.x = this.viewportWidth * 0.5 - point.x * scale;
      this.camera.y = this.viewportHeight * 0.62 - point.y * scale;
    }

    cacheImage(src) {
      const key = imagePath(src);
      if (!key) return null;
      if (this.imageCache.has(key)) return this.imageCache.get(key);
      const image = new Image();
      image.decoding = "async";
      const entry = { status: "loading", image };
      image.onload = () => {
        entry.status = "loaded";
        this.render(performance.now());
      };
      image.onerror = () => {
        entry.status = "error";
      };
      image.src = key;
      this.imageCache.set(key, entry);
      return entry;
    }

    image(src) {
      const entry = this.cacheImage(src);
      return entry?.status === "loaded" ? entry.image : null;
    }

    preload() {
      if (!this.board) return;
      for (const segment of boardSegments(this.board)) this.cacheImage(segment?.src);
      for (const sprite of Array.isArray(this.board.sprites) ? this.board.sprites : []) this.cacheImage(sprite?.src);
      for (const template of Array.isArray(this.board.iconTemplates) ? this.board.iconTemplates : []) this.cacheImage(template?.src);
    }

    visibleRect(extra = 100) {
      const scale = Math.max(0.0001, this.camera.scale || 1);
      return {
        left: ((0 - this.camera.x) / scale) - extra / scale,
        top: ((0 - this.camera.y) / scale) - extra / scale,
        right: ((this.viewportWidth - this.camera.x) / scale) + extra / scale,
        bottom: ((this.viewportHeight - this.camera.y) / scale) + extra / scale
      };
    }

    segmentVisible(segment, rect) {
      const top = Number(segment?.y || 0);
      const bottom = top + Math.max(1, Number(segment?.h || 1));
      return bottom >= rect.top && top <= rect.bottom;
    }

    drawSegments(ctx, rect) {
      const size = boardSize(this.board);
      for (const segment of boardSegments(this.board)) {
        if (!this.segmentVisible(segment, rect)) continue;
        const image = this.image(segment?.src);
        const top = Number(segment.y || 0);
        const height = Math.max(1, Number(segment.h || 1));
        if (!image) {
          ctx.fillStyle = "#101722";
          ctx.fillRect(0, top, size.width, height);
          ctx.strokeStyle = "rgba(142,161,255,.18)";
          ctx.strokeRect(0, top, size.width, height);
          continue;
        }
        const drawTop = Math.max(top, rect.top);
        const drawBottom = Math.min(top + height, rect.bottom);
        const drawHeight = drawBottom - drawTop;
        if (drawHeight <= 0) continue;
        const sourceY = ((drawTop - top) / height) * image.naturalHeight;
        const sourceHeight = (drawHeight / height) * image.naturalHeight;
        ctx.drawImage(image, 0, sourceY, image.naturalWidth, sourceHeight, 0, drawTop, size.width, drawHeight);
      }
    }

    drawPath(ctx, ts) {
      const steps = Array.isArray(this.board?.steps) ? this.board.steps : [];
      const points = steps.map((_, index) => stepPoint(this.board, index)).filter(Boolean);
      if (points.length >= 2) {
        ctx.save();
        ctx.lineCap = "round";
        ctx.lineJoin = "round";
        ctx.shadowColor = "rgba(111, 231, 255, 0.74)";
        ctx.shadowBlur = 9 + Math.sin(ts / 260) * 2;
        ctx.strokeStyle = "rgba(19, 27, 46, .82)";
        ctx.lineWidth = 8;
        ctx.beginPath();
        points.forEach((point, index) => index ? ctx.lineTo(point.x, point.y) : ctx.moveTo(point.x, point.y));
        ctx.stroke();
        ctx.shadowBlur = 5;
        ctx.strokeStyle = "rgba(143, 244, 255, .88)";
        ctx.lineWidth = 3.2;
        ctx.stroke();
        ctx.restore();
      }
      this.drawWalkGlow(ctx, ts);
      this.drawStepBadges(ctx);
    }

    drawWalkGlow(ctx, ts) {
      const stepValue = Number(this.simulation.walkValue ?? this.simulation.step ?? -1);
      const point = this.pointForValue(stepValue);
      if (!point) return;
      const fx = normalizeFx(this.board);
      const pulse = 1 + Math.sin(ts / 145) * 0.08;
      ctx.save();
      ctx.globalCompositeOperation = "screen";
      const gradient = ctx.createRadialGradient(point.x, point.y, 4, point.x, point.y, 62 * pulse * fx.pathGlow);
      gradient.addColorStop(0, "rgba(255, 247, 171, .84)");
      gradient.addColorStop(0.42, "rgba(120, 240, 255, .36)");
      gradient.addColorStop(1, "rgba(120, 240, 255, 0)");
      ctx.fillStyle = gradient;
      ctx.beginPath();
      ctx.arc(point.x, point.y, 70 * pulse * fx.pathGlow, 0, Math.PI * 2);
      ctx.fill();
      ctx.restore();
    }

    pointForValue(value) {
      if (!this.board) return null;
      const steps = Array.isArray(this.board.steps) ? this.board.steps : [];
      if (!steps.length) return entryPoint(this.board);
      const max = steps.length - 1;
      const v = clamp(Number(value), -1, max);
      if (v < 0) return entryPoint(this.board);
      const low = Math.floor(v);
      const high = Math.min(max, Math.ceil(v));
      const t = v - low;
      const a = stepPoint(this.board, low);
      const b = stepPoint(this.board, high) || a;
      if (!a || !b) return null;
      return { x: lerp(a.x, b.x, t), y: lerp(a.y, b.y, t) };
    }

    stepGroups() {
      const groups = new Map();
      const steps = Array.isArray(this.board?.steps) ? this.board.steps : [];
      steps.forEach((step, index) => {
        const point = stepPoint(this.board, index);
        if (!point) return;
        const key = `${Math.round(point.x / 3)}:${Math.round(point.y / 3)}`;
        if (!groups.has(key)) groups.set(key, { point, indexes: [] });
        groups.get(key).indexes.push(index);
      });
      return Array.from(groups.values());
    }

    drawStepBadges(ctx) {
      for (const group of this.stepGroups()) {
        const label = group.indexes.length > 1
          ? group.indexes.slice(0, 3).join(" | ") + (group.indexes.length > 3 ? "..." : "")
          : String(group.indexes[0]);
        const width = group.indexes.length > 1 ? Math.max(42, label.length * 7.4 + 14) : 24;
        const height = group.indexes.length > 1 ? 20 : 24;
        ctx.save();
        ctx.translate(group.point.x + 16, group.point.y - 18);
        ctx.shadowColor = "rgba(0, 0, 0, .28)";
        ctx.shadowBlur = 10;
        ctx.fillStyle = "rgba(7, 11, 28, .62)";
        ctx.strokeStyle = "rgba(245, 251, 255, .82)";
        ctx.lineWidth = 1.25;
        roundRect(ctx, -width / 2, -height / 2, width, height, height / 2);
        ctx.fill();
        ctx.stroke();
        ctx.fillStyle = "#f5fbff";
        ctx.font = `700 ${group.indexes.length > 1 ? 8.5 : 11}px system-ui, sans-serif`;
        ctx.textAlign = "center";
        ctx.textBaseline = "middle";
        ctx.fillText(label, 0, 1);
        ctx.restore();
      }
    }

    drawRewards(ctx, ts) {
      const legacy = Array.isArray(this.board?.rewards) ? this.board.rewards : [];
      const nodes = (Array.isArray(this.board?.rewardNodes) ? this.board.rewardNodes : []).map((node) => {
        const meta = node?.meta && typeof node.meta === "object" ? node.meta : {};
        return { ...node, __rewardNode: true, kind: meta.kind || node.kind || "coin", amount: meta.amount || node.amount || 1 };
      });
      for (const reward of [...legacy, ...nodes]) {
        const point = pointFromLocation(this.board, reward) || (Number.isInteger(reward?.stepIndex) ? stepPoint(this.board, reward.stepIndex) : null);
        if (!point) continue;
        if (reward.__rewardNode) {
          const template = this.iconTemplate(reward.iconTemplateId);
          if (template && this.drawIconTemplate(ctx, template, point, ts, reward)) continue;
        }
        this.drawRewardMarker(ctx, reward, point, ts);
      }
    }

    iconTemplate(id) {
      return (Array.isArray(this.board?.iconTemplates) ? this.board.iconTemplates : [])
        .find((template) => String(template?.id || "") === String(id || "")) || null;
    }

    drawIconTemplate(ctx, template, point, ts, reward) {
      const image = this.image(template?.src);
      if (!image) return false;
      const box = spriteBox(template, image, spriteFrameIndex(template, ts));
      const scale = Math.max(0.1, Number(template.scale || 1));
      const width = Math.max(1, Number(template.width || template.frameWidth || box.sw || 44) * scale);
      const height = Math.max(1, Number(template.height || template.frameHeight || box.sh || 44) * scale);
      const anchorX = clamp(Number(template.anchorX ?? 0.5), 0, 1);
      const anchorY = clamp(Number(template.anchorY ?? 0.5), 0, 1);
      const claimed = this.simulation.claimedRewardIds.has(String(reward?.id || ""));
      ctx.save();
      ctx.translate(point.x + Number(template.offsetX || 0), point.y + Number(template.offsetY || 0));
      ctx.shadowColor = "rgba(0,0,0,.32)";
      ctx.shadowBlur = 14;
      ctx.drawImage(image, box.sx, box.sy, box.sw, box.sh, -width * anchorX, -height * anchorY, width, height);
      ctx.shadowBlur = 0;
      if (claimed) this.drawCheck(ctx, width * (1 - anchorX) - 12, -height * anchorY + 12, 18);
      ctx.restore();
      return true;
    }

    drawRewardMarker(ctx, reward, point, ts) {
      const size = Math.max(26, Number(this.board?.meta?.ui?.rewardMarker?.size || 44));
      const radius = size / 2;
      const kind = String(reward?.kind || reward?.meta?.kind || "coin");
      const claimed = this.simulation.claimedRewardIds.has(String(reward?.id || ""));
      const unlocked = Number(this.simulation.step || -1) >= Number(reward?.stepIndex ?? 999999);
      const pulse = unlocked && !claimed ? 1 + Math.sin(ts / 120) * 0.045 : 1;
      ctx.save();
      ctx.translate(point.x, point.y);
      ctx.scale(pulse, pulse);
      ctx.shadowColor = "rgba(0,0,0,.32)";
      ctx.shadowBlur = 16;
      ctx.fillStyle = claimed ? "rgba(34,42,62,.86)" : "rgba(9,15,32,.9)";
      ctx.strokeStyle = claimed ? "rgba(235,243,255,.72)" : rewardTone(kind);
      ctx.lineWidth = 3;
      ctx.beginPath();
      ctx.arc(0, 0, radius, 0, Math.PI * 2);
      ctx.fill();
      ctx.stroke();
      ctx.shadowBlur = 0;
      if (claimed) {
        this.drawCheck(ctx, 0, 0, radius * 0.95);
      } else {
        ctx.fillStyle = rewardTone(kind);
        ctx.font = `800 ${Math.max(13, size * 0.38)}px system-ui, sans-serif`;
        ctx.textAlign = "center";
        ctx.textBaseline = "middle";
        ctx.fillText(kind === "coin" ? "$" : kind.slice(0, 1).toUpperCase(), 0, 1);
      }
      ctx.restore();
    }

    drawCheck(ctx, x, y, size) {
      ctx.save();
      ctx.fillStyle = "rgba(7,11,28,.82)";
      ctx.strokeStyle = "rgba(245,251,255,.92)";
      ctx.lineWidth = 1.4;
      ctx.beginPath();
      ctx.arc(x, y, size / 2, 0, Math.PI * 2);
      ctx.fill();
      ctx.stroke();
      ctx.fillStyle = "#f5fbff";
      ctx.font = `800 ${size * 0.64}px system-ui, sans-serif`;
      ctx.textAlign = "center";
      ctx.textBaseline = "middle";
      ctx.fillText("✓", x, y + 1);
      ctx.restore();
    }

    spritesForSlot(slot) {
      return (Array.isArray(this.board?.sprites) ? this.board.sprites : [])
        .filter((sprite) => (sprite.visible ?? true) !== false)
        .filter((sprite) => String(sprite.layerSlot || LAYER_SLOT.BACK) === slot)
        .sort((a, b) => Number(a.zIndex || 0) - Number(b.zIndex || 0));
    }

    drawSprites(ctx, ts, slot) {
      for (const sprite of this.spritesForSlot(slot)) {
        const point = pointFromLocation(this.board, sprite);
        const image = this.image(sprite?.src);
        if (!point || !image) continue;
        const active = spriteStateFor(sprite, this.simulation.step);
        const data = active ? { ...sprite, ...active } : sprite;
        const width = Math.max(1, Number(data.width || sprite.width || 48));
        const height = Math.max(1, Number(data.height || sprite.height || 48));
        const box = spriteBox(data, image, spriteFrameIndex(data, ts));
        ctx.save();
        ctx.globalAlpha = Number(data.opacity ?? 1);
        ctx.shadowColor = "rgba(0,0,0,.24)";
        ctx.shadowBlur = slot === LAYER_SLOT.FRONT ? 16 : 8;
        ctx.drawImage(image, box.sx, box.sy, box.sw, box.sh, point.x - width / 2, point.y - height / 2, width, height);
        ctx.restore();
      }
    }

    drawPlayers(ctx, ts) {
      const selfPoint = this.pointForValue(Number(this.simulation.walkValue ?? this.simulation.step ?? -1)) || entryPoint(this.board);
      const friends = this.simulation.showFriends ? this.simulation.friends || [] : [];
      friends.forEach((friend, index) => {
        const base = Number(friend.step ?? (Number(this.simulation.step || 0) - index - 1));
        const point = this.pointForValue(base);
        if (!point) return;
        this.drawAvatar(ctx, point.x + (index % 2 ? -42 : 42), point.y - 18 + (index % 3) * 18, 28, friend.label || `F${index + 1}`, "#7e9cff", ts, false);
      });
      if (selfPoint) {
        this.drawAvatar(ctx, selfPoint.x, selfPoint.y - 34, 52, "ME", "#ff91dc", ts, true);
      }
    }

    drawAvatar(ctx, x, y, size, label, color, ts, self) {
      const pulse = self ? 1 + Math.sin(ts / 180) * 0.025 : 1;
      ctx.save();
      ctx.translate(x, y);
      ctx.scale(pulse, pulse);
      ctx.shadowColor = self ? "rgba(255,145,220,.4)" : "rgba(0,0,0,.28)";
      ctx.shadowBlur = self ? 18 : 10;
      ctx.fillStyle = color;
      ctx.strokeStyle = "rgba(245,251,255,.92)";
      ctx.lineWidth = self ? 3 : 2;
      ctx.beginPath();
      ctx.arc(0, 0, size / 2, 0, Math.PI * 2);
      ctx.fill();
      ctx.stroke();
      ctx.shadowBlur = 0;
      ctx.fillStyle = "#fff";
      ctx.font = `800 ${size * 0.34}px system-ui, sans-serif`;
      ctx.textAlign = "center";
      ctx.textBaseline = "middle";
      ctx.fillText(String(label || "?").slice(0, 2).toUpperCase(), 0, 1);
      ctx.restore();
    }

    drawClouds(ctx, ts, rect) {
      const fx = normalizeFx(this.board);
      if (fx.clouds <= 0) return;
      const size = boardSize(this.board);
      ctx.save();
      ctx.globalCompositeOperation = "screen";
      for (let i = 0; i < 10; i += 1) {
        const x = ((i * 197 + ts * 0.006) % (size.width + 240)) - 120;
        const y = rect.top + 90 + (i % 5) * 180;
        const radius = 70 + (i % 3) * 26;
        const gradient = ctx.createRadialGradient(x, y, radius * 0.2, x, y, radius);
        gradient.addColorStop(0, `rgba(135, 201, 255, ${0.08 * fx.clouds})`);
        gradient.addColorStop(1, "rgba(135, 201, 255, 0)");
        ctx.fillStyle = gradient;
        ctx.beginPath();
        ctx.arc(x, y, radius, 0, Math.PI * 2);
        ctx.fill();
      }
      ctx.restore();
    }

    render(ts = performance.now()) {
      if (!this.canvas || !this.ctx || !this.board) return;
      this.resize();
      const ctx = this.ctx;
      const rect = this.visibleRect(140);
      ctx.setTransform(1, 0, 0, 1, 0, 0);
      ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
      ctx.fillStyle = "#04070f";
      ctx.fillRect(0, 0, this.canvas.width, this.canvas.height);
      ctx.setTransform(this.dpr * this.camera.scale, 0, 0, this.dpr * this.camera.scale, this.dpr * this.camera.x, this.dpr * this.camera.y);
      this.drawSegments(ctx, rect);
      this.drawSprites(ctx, ts, LAYER_SLOT.BACK);
      this.drawPath(ctx, ts);
      this.drawPlayers(ctx, ts);
      this.drawRewards(ctx, ts);
      this.drawSprites(ctx, ts, LAYER_SLOT.FRONT);
      this.drawClouds(ctx, ts, rect);
      ctx.setTransform(1, 0, 0, 1, 0, 0);
      if (this.onAfterRender) this.onAfterRender(this);
    }
  }

  function spriteStateFor(sprite, step) {
    const states = sprite?.states && typeof sprite.states === "object" ? sprite.states : null;
    if (!states) return null;
    const enabled = Array.isArray(sprite?.enabledStates) ? sprite.enabledStates : ["idle"];
    const enabledSet = new Set(enabled.concat("idle"));
    const isEnabled = (name) => enabledSet.has(name);
    const trigger = Number(sprite?.stepIndex ?? sprite?.meta?.stepIndex ?? -1);
    const current = Number(step ?? -1);
    if (isEnabled("claimed") && states.claimed && sprite?.meta?.claimed) return states.claimed;
    if (isEnabled("ready") && states.ready && trigger >= 0 && current >= trigger) return states.ready;
    if (isEnabled("notReady") && states.notReady && trigger >= 0 && current < trigger) return states.notReady;
    return states.idle || null;
  }

  function roundRect(ctx, x, y, width, height, radius) {
    const r = Math.min(radius, width / 2, height / 2);
    ctx.beginPath();
    ctx.moveTo(x + r, y);
    ctx.arcTo(x + width, y, x + width, y + height, r);
    ctx.arcTo(x + width, y + height, x, y + height, r);
    ctx.arcTo(x, y + height, x, y, r);
    ctx.arcTo(x, y, x + width, y, r);
    ctx.closePath();
  }

  window.DekpokeMileageRuntime = {
    DEFAULT_FX,
    LAYER_SLOT,
    create(canvas, options) {
      return new MileageRuntime(canvas, options);
    },
    boardSize,
    pointFromLocation,
    stepPoint,
    spriteFrameIndex,
    spriteBox
  };
})();
