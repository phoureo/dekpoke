(function () {
  "use strict";

  const Editor = window.DekpokeEditor;
  const boot = window.MILEAGE_EDITOR_BOOT || {};
  const boardCode = String(boot.boardCode || "main");
  const apiUrl = new URL(String(boot.apiUrl || "mileage-api.php"), window.location.href);
  apiUrl.searchParams.set("boardCode", boardCode);
  const DEFAULT_UPLOADED_SPRITE_EDGE_FADE = 3;

  const refs = {
    root: document.getElementById("mileageEditorRoot"),
    workspace: document.getElementById("mileageWorkspace"),
    stageHost: document.getElementById("mapStage"),
    runtimeFrame: document.getElementById("runtimePreviewFrame"),
    viewButtons: Array.from(document.querySelectorAll("[data-view-mode]")),
    toolButtons: Array.from(document.querySelectorAll("[data-tool]")),
    activeToolLabel: document.getElementById("activeToolLabel"),
    selectionInspector: document.getElementById("selectionInspector"),
    draftStatePill: document.getElementById("draftStatePill"),
    selectionPill: document.getElementById("selectionPill"),
    saveDraftButton: document.getElementById("saveDraftButton"),
    stickyToolButton: document.getElementById("stickyToolButton"),
    publishButton: document.getElementById("publishButton"),
    refreshButton: document.getElementById("refreshButton"),
    fitButton: document.getElementById("fitButton"),
    undoButton: document.getElementById("undoButton"),
    redoButton: document.getElementById("redoButton"),
    boardTitleInput: document.getElementById("boardTitleInput"),
    rewardMarkerSizeInput: document.getElementById("rewardMarkerSizeInput"),
    pickupScaleInput: document.getElementById("pickupScaleInput"),
    pickupCountInput: document.getElementById("pickupCountInput"),
    fxPathGlowInput: document.getElementById("fxPathGlowInput"),
    fxCloudInput: document.getElementById("fxCloudInput"),
    fxFriendCountInput: document.getElementById("fxFriendCountInput"),
    quickPalette: document.getElementById("quickPalette"),
    segmentList: document.getElementById("segmentList"),
    addTopSegmentButton: document.getElementById("addTopSegmentButton"),
    addBottomSegmentButton: document.getElementById("addBottomSegmentButton"),
    deleteSegmentButton: document.getElementById("deleteSegmentButton"),
    iconUploadInput: document.getElementById("iconUploadInput"),
    addIconTemplateButton: document.getElementById("addIconTemplateButton"),
    iconTemplateList: document.getElementById("iconTemplateList"),
    addRewardTemplateButton: document.getElementById("addRewardTemplateButton"),
    rewardTemplateList: document.getElementById("rewardTemplateList"),
    refreshAssetButton: document.getElementById("refreshAssetButton"),
    assetSearchInput: document.getElementById("assetSearchInput"),
    assetViewButtons: Array.from(document.querySelectorAll("[data-asset-view]")),
    assetPreview: document.getElementById("assetPreview"),
    assetBrowserList: document.getElementById("assetBrowserList"),
    versionList: document.getElementById("versionList"),
    jsonPreview: document.getElementById("jsonPreview"),
    layerList: document.getElementById("layerList"),
    layerCountLabel: document.getElementById("layerCountLabel"),
    previewPlayButton: document.getElementById("previewPlayButton"),
    previewZoomOutButton: document.getElementById("previewZoomOutButton"),
    previewZoomInButton: document.getElementById("previewZoomInButton"),
    previewFocusButton: document.getElementById("previewFocusButton"),
    previewStepRange: document.getElementById("previewStepRange"),
    previewSpeedRange: document.getElementById("previewSpeedRange"),
    previewStepLabel: document.getElementById("previewStepLabel"),
    previewFriendsButton: document.getElementById("previewFriendsButton"),
    previewFitButton: document.getElementById("previewFitButton"),
    zoomLabel: document.getElementById("zoomLabel"),
    stepCountLabel: document.getElementById("stepCountLabel"),
    rewardCountLabel: document.getElementById("rewardCountLabel"),
    rewardNodeCountLabel: document.getElementById("rewardNodeCountLabel"),
    spriteCountLabel: document.getElementById("spriteCountLabel"),
    assetWarningLabel: document.getElementById("assetWarningLabel")
  };

  const rangeSyncInputs = Array.from(document.querySelectorAll("[data-range-target]"));

  const state = {
    board: null,
    liveBoard: null,
    versions: { versions: [] },
    saveAllowed: false,
    hasDraft: false,
    dirty: false,
    tool: "select",
    stickyTool: false,
    viewMode: "design",
    selected: [],
    selectedSegmentId: "",
    selectedIconTemplateId: "",
    selectedRewardTemplateId: "",
    spriteRatioLocked: true,
    spacePan: false,
    marquee: null,
    marqueeDragNode: null,
    lastPointer: null,
    dragStarted: false,
    dragPayload: null,
    expandedClusterKey: "",
    assets: [],
    assetSearch: "",
    assetView: "grid",
    selectedAssetPath: "",
    previewPlaying: false,
    previewFriends: true,
    previewSpeed: 1,
    previewStep: -1,
    lastPreviewStepAt: 0,
    imageCache: new Map(),
    softSpriteCache: new Map(),
    stage: null,
    previewReady: false,
    previewBoardDirty: true,
    inspectorEditing: false,
    layerPointerDrag: null,
    layers: {},
    transformer: null,
    ghost: null,
    selectionRect: null,
    middlePan: null,
    history: null
  };

  state.history = new Editor.HistoryStack({
    onChange: refreshHistoryButtons
  });

  function emptyBoard() {
    return {
      boardCode,
      version: 2,
      title: "Mileage Board",
      entry: { x: 0.5, y: 0.95 },
      image: { width: 1200, height: 1, source: "", segments: [] },
      steps: [],
      rewards: [],
      sprites: [],
      iconTemplates: [],
      rewardTemplates: [],
      rewardNodes: [],
      meta: {
      ui: {
        rewardMarker: { size: 44 },
        currencyPickup: { scale: 1.3, countMultiplier: 1.45 }
        },
        fx: { pathGlow: 1, pathLine: 1, clouds: 1, ambience: 1, friendCount: 3, selfPulse: 1 },
        editor: { layerPolicy: "path-first" }
      }
    };
  }

  function plainObject(value) {
    return value && typeof value === "object" && !Array.isArray(value);
  }

  function isUploadedSpritePath(path) {
    const value = String(path || "").trim().toLowerCase();
    return value.startsWith("uploads/") || value.startsWith("images/uploads/");
  }

  function defaultSpriteEdgeFade(path, rawValue = undefined) {
    const fallback = isUploadedSpritePath(path) ? DEFAULT_UPLOADED_SPRITE_EDGE_FADE : 0;
    const parsed = Number(rawValue);
    return Editor.clamp(Number.isFinite(parsed) ? parsed : fallback, 0, 64);
  }

  function boardWidth() {
    return Math.max(1, Number(state.board?.image?.width || 1));
  }

  function boardSegments() {
    return Array.isArray(state.board?.image?.segments) ? state.board.image.segments : [];
  }

  function segmentsHeight(segments = boardSegments()) {
    return Math.max(1, segments.reduce((sum, segment) => sum + Math.max(1, Number(segment?.h || 1)), 0));
  }

  function normalizeSegmentId(value) {
    return String(value || "").trim().toLowerCase().replace(/[^a-z0-9_-]+/g, "-").replace(/^[-_]+|[-_]+$/g, "") || "segment_001";
  }

  function finalizeSegments() {
    let cursor = 0;
    const segments = boardSegments().filter((segment) => plainObject(segment));
    state.board.image.segments = segments.map((segment, index) => {
      const next = {
        id: normalizeSegmentId(segment.id || `segment_${String(index + 1).padStart(3, "0")}`),
        src: String(segment.src || ""),
        h: Math.max(1, Math.round(Number(segment.h || 1))),
        y: cursor
      };
      cursor += next.h;
      return next;
    });
    state.board.image.height = Math.max(1, cursor);
    if (!state.selectedSegmentId || !segmentById(state.selectedSegmentId)) {
      state.selectedSegmentId = String(state.board.image.segments[0]?.id || "");
    }
  }

  function normalizeBoard() {
    state.board = plainObject(state.board) ? state.board : emptyBoard();
    state.board.boardCode = normalizeSegmentId(state.board.boardCode || boardCode);
    state.board.version = Math.max(2, Math.round(Number(state.board.version || 2)));
    state.board.title = String(state.board.title || "Mileage Board");
    state.board.image = plainObject(state.board.image) ? state.board.image : emptyBoard().image;
    state.board.image.width = Math.max(1, Math.round(Number(state.board.image.width || 1)));
    if (!Array.isArray(state.board.image.segments)) {
      state.board.image.segments = [];
    }
    if (!state.board.image.segments.length && String(state.board.image.source || "").trim()) {
      state.board.image.segments.push({
        id: "segment_001",
        src: String(state.board.image.source || ""),
        h: Math.max(1, Math.round(Number(state.board.image.height || 1))),
        y: 0
      });
    }
    finalizeSegments();
    state.board.steps = Array.isArray(state.board.steps) ? state.board.steps : [];
    state.board.rewards = Array.isArray(state.board.rewards) ? state.board.rewards : [];
    state.board.sprites = Array.isArray(state.board.sprites) ? state.board.sprites : [];
    state.board.iconTemplates = Array.isArray(state.board.iconTemplates) ? state.board.iconTemplates : [];
    state.board.rewardTemplates = Array.isArray(state.board.rewardTemplates) ? state.board.rewardTemplates : [];
    state.board.rewardNodes = Array.isArray(state.board.rewardNodes) ? state.board.rewardNodes : [];
    state.board.meta = plainObject(state.board.meta) ? state.board.meta : {};
    const ui = plainObject(state.board.meta.ui) ? state.board.meta.ui : {};
    state.board.meta.ui = {
      ...ui,
      rewardMarker: {
        ...(plainObject(ui.rewardMarker) ? ui.rewardMarker : {}),
        size: Editor.clamp(Number(ui.rewardMarker?.size || 44), 26, 96)
      },
      currencyPickup: {
        ...(plainObject(ui.currencyPickup) ? ui.currencyPickup : {}),
        scale: Editor.clamp(Number(ui.currencyPickup?.scale || 1.3), 0.7, 2.4),
        countMultiplier: Editor.clamp(Number(ui.currencyPickup?.countMultiplier || 1.45), 0.7, 3.2)
      }
    };
    const fx = plainObject(state.board.meta.fx) ? state.board.meta.fx : {};
    state.board.meta.fx = {
      pathGlow: Editor.clamp(Number(fx.pathGlow ?? 1), 0, 2),
      pathLine: Editor.clamp(Number(fx.pathLine ?? 1), 0, 2),
      clouds: Editor.clamp(Number(fx.clouds ?? 1), 0, 2),
      ambience: Editor.clamp(Number(fx.ambience ?? 1), 0, 2),
      friendCount: Editor.clamp(Math.round(Number(fx.friendCount ?? 3)), 0, 12),
      selfPulse: Editor.clamp(Number(fx.selfPulse ?? 1), 0, 2)
    };
    state.board.meta.editor = plainObject(state.board.meta.editor) ? state.board.meta.editor : {};
    state.board.meta.editor.layerPolicy = "path-first";
    state.board.steps.forEach((step, index) => {
      step.i = index;
      if (!plainObject(step.meta)) step.meta = {};
      normalizeLayerFields(step, "path", index);
      normalizeEntityLocation(step);
    });
    state.board.rewards.forEach((reward, index) => {
      reward.id = String(reward.id || Editor.id(`reward${index + 1}`));
      reward.kind = ["coin", "ticket", "gem", "potion", "item"].includes(String(reward.kind || "")) ? reward.kind : "coin";
      reward.amount = Math.max(1, Number(reward.amount || 1));
      if (!plainObject(reward.meta)) reward.meta = {};
      normalizeLayerFields(reward, "reward", index);
      normalizeEntityLocation(reward);
    });
    state.board.sprites.forEach((sprite, index) => {
      sprite.id = String(sprite.id || Editor.id(`sprite${index + 1}`));
      sprite.width = Math.max(1, Math.round(Number(sprite.width || 48)));
      sprite.height = Math.max(1, Math.round(Number(sprite.height || 48)));
      sprite.columns = Math.max(1, Math.round(Number(sprite.columns || 1)));
      sprite.rows = Math.max(1, Math.round(Number(sprite.rows || 1)));
      sprite.frameCount = Editor.clamp(Math.round(Number(sprite.frameCount || sprite.columns * sprite.rows)), 1, sprite.columns * sprite.rows);
      sprite.frameWidth = Math.max(0, Math.round(Number(sprite.frameWidth || 0)));
      sprite.frameHeight = Math.max(0, Math.round(Number(sprite.frameHeight || 0)));
      sprite.fps = Editor.clamp(Number(sprite.fps || 12), 1, 60);
      sprite.mode = ["static", "once", "loop", "pingpong"].includes(String(sprite.mode || "")) ? sprite.mode : "loop";
      sprite.autoplay = sprite.autoplay !== false;
      if (!plainObject(sprite.meta)) sprite.meta = {};
      sprite.meta.editorOpacity = Editor.clamp(Number(sprite.meta.editorOpacity ?? 1), 0.1, 1);
      sprite.meta.edgeFade = defaultSpriteEdgeFade(sprite.src, sprite.meta.edgeFade);
      sprite.stepIndex = Number.isFinite(Number(sprite.stepIndex ?? sprite.meta.stepIndex)) ? Math.max(-1, Math.round(Number(sprite.stepIndex ?? sprite.meta.stepIndex))) : -1;
      sprite.enabledStates = Array.isArray(sprite.enabledStates) ? sprite.enabledStates : ["idle"];
      if (!sprite.enabledStates.includes("idle")) sprite.enabledStates.unshift("idle");
      normalizeLayerFields(sprite, "decor-back", index);
      normalizeAnimationStates(sprite);
      normalizeEntityLocation(sprite);
    });
    state.board.iconTemplates.forEach((template, index) => {
      template.id = String(template.id || `icon_${String(index + 1).padStart(3, "0")}`);
      template.label = String(template.label || "");
      template.src = String(template.src || "");
      template.frameX = Math.max(0, Math.round(Number(template.frameX || 0)));
      template.frameY = Math.max(0, Math.round(Number(template.frameY || 0)));
      template.frameWidth = Math.max(0, Math.round(Number(template.frameWidth || 0)));
      template.frameHeight = Math.max(0, Math.round(Number(template.frameHeight || 0)));
      template.columns = Math.max(1, Math.round(Number(template.columns || 1)));
      template.rows = Math.max(1, Math.round(Number(template.rows || 1)));
      template.frameCount = Editor.clamp(Math.round(Number(template.frameCount || template.columns * template.rows)), 1, template.columns * template.rows);
      template.fps = Editor.clamp(Number(template.fps || 12), 1, 60);
      template.mode = ["once", "loop", "pingpong"].includes(String(template.mode || "")) ? template.mode : "loop";
      template.scale = Editor.clamp(Number(template.scale || 1), 0.1, 4);
      template.anchorX = Editor.clamp(Number(template.anchorX ?? 0.5), 0, 1);
      template.anchorY = Editor.clamp(Number(template.anchorY ?? 0.5), 0, 1);
      template.offsetX = Number(template.offsetX || 0);
      template.offsetY = Number(template.offsetY || 0);
      if (!plainObject(template.meta)) template.meta = {};
      normalizeAnimationStates(template);
    });
    state.board.rewardTemplates.forEach((template, index) => {
      template.id = String(template.id || `reward_template_${String(index + 1).padStart(3, "0")}`);
      template.label = String(template.label || "");
      template.rewardTemplateId = String(template.rewardTemplateId || "");
      template.mode = String(template.mode || "fixed") === "random" ? "random" : "fixed";
      if (!plainObject(template.meta)) template.meta = {};
    });
    state.board.rewardNodes.forEach((node, index) => {
      node.id = String(node.id || `reward_node_${String(index + 1).padStart(3, "0")}`);
      node.label = String(node.label || "");
      node.iconTemplateId = String(node.iconTemplateId || "");
      node.rewardTemplateId = String(node.rewardTemplateId || "");
      if (!plainObject(node.meta)) node.meta = {};
      normalizeLayerFields(node, "reward", index);
      normalizeEntityLocation(node);
    });
  }

  function normalizeLayerSlot(value, fallback = "decor-back") {
    const slot = String(value || fallback);
    return ["background", "decor-back", "path", "reward", "decor-front", "fx-front"].includes(slot) ? slot : fallback;
  }

  function normalizeLayerFields(entity, fallbackSlot, index) {
    entity.visible = entity.visible !== false;
    entity.locked = entity.locked === true;
    entity.layerSlot = normalizeLayerSlot(entity.layerSlot, fallbackSlot);
    entity.zIndex = Number.isFinite(Number(entity.zIndex)) ? Number(entity.zIndex) : index;
  }

  function normalizeAnimationStates(entity) {
    const states = plainObject(entity.states) ? entity.states : {};
    const out = {};
    ["idle", "touch", "notReady", "ready", "claimed"].forEach((name) => {
      const source = states[name] || (name === "notReady" ? states.notready : null);
      if (!plainObject(source)) return;
      out[name] = {
        label: String(source.label || ""),
        frameX: Math.max(0, Math.round(Number(source.frameX || 0))),
        frameY: Math.max(0, Math.round(Number(source.frameY || 0))),
        frameWidth: Math.max(0, Math.round(Number(source.frameWidth || 0))),
        frameHeight: Math.max(0, Math.round(Number(source.frameHeight || 0))),
        columns: Math.max(1, Math.round(Number(source.columns || entity.columns || 1))),
        rows: Math.max(1, Math.round(Number(source.rows || entity.rows || 1))),
        frameCount: Math.max(1, Math.round(Number(source.frameCount || entity.frameCount || 1))),
        frameIndex: Math.max(0, Math.round(Number(source.frameIndex || 0))),
        fps: Editor.clamp(Number(source.fps || entity.fps || 12), 1, 60),
        mode: ["static", "once", "loop", "pingpong"].includes(String(source.mode || "")) ? source.mode : "loop",
        width: Math.max(0, Math.round(Number(source.width || 0))),
        height: Math.max(0, Math.round(Number(source.height || 0))),
        opacity: Editor.clamp(Number(source.opacity ?? 1), 0, 1)
      };
    });
    entity.states = out;
  }

  function segmentById(segmentId) {
    const id = normalizeSegmentId(segmentId);
    return boardSegments().find((segment) => String(segment.id || "") === id) || boardSegments()[0] || null;
  }

  function segmentForY(boardY) {
    let last = boardSegments()[0] || null;
    for (const segment of boardSegments()) {
      last = segment;
      const top = Number(segment.y || 0);
      const bottom = top + Math.max(1, Number(segment.h || 1));
      if (boardY >= top && boardY <= bottom) return segment;
    }
    return last;
  }

  function normalizeEntityLocation(entity) {
    if (!entity) return;
    const segment = segmentById(entity.segmentId);
    if (segment && typeof entity.localX === "number" && typeof entity.localY === "number") {
      setEntityPoint(entity, entity.localX * boardWidth(), Number(segment.y || 0) + entity.localY * Math.max(1, Number(segment.h || 1)));
      return;
    }
    setEntityPoint(entity, Editor.clamp(Number(entity.x || 0), 0, 1) * boardWidth(), Editor.clamp(Number(entity.y || 0), 0, 1) * segmentsHeight());
  }

  function setEntityPoint(entity, x, y) {
    const width = boardWidth();
    const height = segmentsHeight();
    const boardX = Editor.clamp(Number(x || 0), 0, width);
    const boardY = Editor.clamp(Number(y || 0), 0, height);
    const segment = segmentForY(boardY) || boardSegments()[0] || { id: "segment_001", y: 0, h: height };
    entity.segmentId = String(segment.id || "segment_001");
    entity.localX = Editor.clamp(boardX / width, 0, 1);
    entity.localY = Editor.clamp((boardY - Number(segment.y || 0)) / Math.max(1, Number(segment.h || 1)), 0, 1);
    entity.x = Editor.clamp(boardX / width, 0, 1);
    entity.y = Editor.clamp(boardY / height, 0, 1);
  }

  function pointOf(entity) {
    if (!entity) return { x: 0, y: 0 };
    const segment = segmentById(entity.segmentId);
    if (segment && typeof entity.localX === "number" && typeof entity.localY === "number") {
      return {
        x: Editor.clamp(entity.localX, 0, 1) * boardWidth(),
        y: Number(segment.y || 0) + Editor.clamp(entity.localY, 0, 1) * Math.max(1, Number(segment.h || 1))
      };
    }
    return {
      x: Editor.clamp(Number(entity.x || 0), 0, 1) * boardWidth(),
      y: Editor.clamp(Number(entity.y || 0), 0, 1) * segmentsHeight()
    };
  }

  function api(action, body = null) {
    const url = new URL(apiUrl.toString());
    url.searchParams.set("action", action);
    return fetch(url, {
      method: body ? "POST" : "GET",
      credentials: "same-origin",
      cache: "no-store",
      headers: body ? { "Content-Type": "application/json" } : undefined,
      body: body ? JSON.stringify(body) : null
    }).then(async (response) => {
      const data = await response.json().catch(() => null);
      if (!response.ok || !data || data.ok === false) {
        throw new Error(data?.message || data?.code || `HTTP ${response.status}`);
      }
      return data;
    });
  }

  function initStage() {
    const rect = refs.stageHost.getBoundingClientRect();
    state.stage = new Konva.Stage({
      container: refs.stageHost,
      width: Math.max(1, rect.width),
      height: Math.max(1, rect.height)
    });
    const backgroundLayer = new Konva.Layer({ listening: false });
    const objectLayer = new Konva.Layer();
    const fxLayer = new Konva.Layer({ listening: false });
    const uiLayer = new Konva.Layer();
    state.layers.background = backgroundLayer;
    state.layers.decorBack = objectLayer;
    state.layers.path = objectLayer;
    state.layers.reward = objectLayer;
    state.layers.decorFront = objectLayer;
    state.layers.fxFront = fxLayer;
    state.layers.ui = uiLayer;
    state.layers.simulation = uiLayer;
    [backgroundLayer, objectLayer, fxLayer, uiLayer].forEach((layer) => state.stage.add(layer));
    state.transformer = new Konva.Transformer({
      rotateEnabled: false,
      keepRatio: true,
      borderStroke: "#8ea1ff",
      borderDash: [8, 6],
      anchorFill: "#eef3fb",
      anchorStroke: "#8ea1ff",
      anchorSize: 9,
      enabledAnchors: ["top-left", "top-right", "bottom-left", "bottom-right"]
    });
    state.layers.ui.add(state.transformer);
    state.ghost = new Konva.Group({
      visible: false,
      listening: false
    });
    state.layers.ui.add(state.ghost);
    state.selectionRect = new Konva.Rect({
      visible: false,
      fill: "rgba(142, 161, 255, 0.14)",
      stroke: "#8ea1ff",
      dash: [8, 6],
      listening: false
    });
    state.layers.ui.add(state.selectionRect);

    state.stage.on("mousedown touchstart", handlePointerDown);
    state.stage.on("mousemove touchmove", handlePointerMove);
    state.stage.on("mouseup touchend", handlePointerUp);
    state.stage.on("contextmenu", handleContextMenu);
    state.stage.on("wheel", handleWheel);
    state.stage.container().addEventListener("mousedown", handleMiddlePanMouseDown);
    state.stage.container().addEventListener("auxclick", (event) => {
      if (event.button === 1) event.preventDefault();
    });
    window.addEventListener("resize", resizeStage, { passive: true });
    window.addEventListener("keydown", handleKeyDown);
    window.addEventListener("keyup", handleKeyUp);
    window.addEventListener("mousemove", handleMiddlePanMouseMove);
    window.addEventListener("mouseup", stopMiddlePan);
  }

  function resizeStage() {
    if (!state.stage) return;
    const rect = refs.stageHost.getBoundingClientRect();
    state.stage.width(Math.max(1, rect.width));
    state.stage.height(Math.max(1, rect.height));
    refreshStatus();
    resizePreview();
  }

  function editorCursor() {
    if (state.middlePan) return "grabbing";
    if (state.tool === "pan" || state.spacePan) return "grab";
    return state.tool === "select" ? "default" : "crosshair";
  }

  function handleMiddlePanMouseDown(event) {
    if (!state.stage || event.button !== 1) return;
    event.preventDefault();
    state.middlePan = {
      clientX: event.clientX,
      clientY: event.clientY,
      stageX: state.stage.x(),
      stageY: state.stage.y()
    };
    state.stage.container().style.cursor = "grabbing";
  }

  function handleMiddlePanMouseMove(event) {
    if (!state.middlePan || !state.stage) return;
    event.preventDefault();
    const dx = event.clientX - state.middlePan.clientX;
    const dy = event.clientY - state.middlePan.clientY;
    state.stage.position({
      x: state.middlePan.stageX + dx,
      y: state.middlePan.stageY + dy
    });
    state.stage.batchDraw();
    refreshStatus();
  }

  function stopMiddlePan() {
    if (!state.middlePan || !state.stage) return;
    state.middlePan = null;
    state.stage.container().style.cursor = editorCursor();
  }

  function uniqueLayers() {
    return Array.from(new Set(Object.values(state.layers).filter(Boolean)));
  }

  function fitView() {
    if (!state.stage || !state.board) return;
    const padding = 48;
    const scaleX = (state.stage.width() - padding * 2) / Math.max(1, boardWidth());
    const scaleY = (state.stage.height() - padding * 2) / Math.max(1, segmentsHeight());
    const scale = Editor.clamp(Math.min(scaleX, scaleY), 0.04, 2.5);
    state.stage.scale({ x: scale, y: scale });
    state.stage.position({
      x: (state.stage.width() - boardWidth() * scale) / 2,
      y: (state.stage.height() - segmentsHeight() * scale) / 2
    });
    state.stage.batchDraw();
    refreshStatus();
  }

  function resizePreview() {
    syncPreview();
  }

  function initPreviewRuntime() {
    if (!refs.runtimeFrame || state.previewReady) return;
    state.previewReady = true;
    refs.runtimeFrame.addEventListener("load", () => {
      state.previewBoardDirty = true;
      postPreviewBoard({ autoplay: false, forceBoard: true });
    });
    window.addEventListener("resize", resizePreview, { passive: true });
  }

  function previewFrameReady() {
    try {
      return Boolean(refs.runtimeFrame?.contentDocument?.querySelector("#boardCanvas"));
    } catch (error) {
      return false;
    }
  }

  function ensurePreviewFrame(force = false) {
    if (!refs.runtimeFrame) return;
    const expected = new URL(`mileage.php?boardCode=${encodeURIComponent(boardCode)}&preview=1`, window.location.href).toString();
    const current = refs.runtimeFrame.src ? new URL(refs.runtimeFrame.src, window.location.href).toString() : "";
    if (force || !current || current === "about:blank" || !previewFrameReady()) {
      refs.runtimeFrame.src = expected;
      state.previewBoardDirty = true;
      window.setTimeout(() => postPreviewBoard({ autoplay: false, forceBoard: true }), 900);
      return;
    }
    postPreviewBoard({ forceBoard: state.previewBoardDirty });
  }

  function buildPreviewSimulation() {
    const count = Math.max(0, Math.round(Number(state.board?.meta?.fx?.friendCount ?? 3)));
    const currentStep = Number(state.previewStep ?? -1);
    const wholeStep = Math.floor(currentStep);
    const friends = Array.from({ length: state.previewFriends ? count : 0 }, (_, index) => ({
      label: `F${index + 1}`,
      step: Math.max(-1, wholeStep - index - 1)
    }));
    return {
      step: wholeStep,
      walkValue: currentStep,
      walkFrom: state.previewPlaying ? Math.max(-1, wholeStep - 1) : wholeStep,
      speed: Number(state.previewSpeed || 1),
      autoplay: state.previewPlaying,
      friends,
      showFriends: state.previewFriends,
      claimedRewardIds: [],
      recentRewardIds: []
    };
  }

  function postPreviewBoard(options = {}) {
    if (!refs.runtimeFrame?.contentWindow || !state.board) return;
    const includeBoard = options.forceBoard || state.previewBoardDirty;
    refs.runtimeFrame.contentWindow.postMessage({
      type: "dekpoke-mileage-preview-board",
      board: includeBoard ? state.board : null,
      simulation: {
        ...buildPreviewSimulation(),
        autoplay: Boolean(options.autoplay ?? state.previewPlaying)
      }
    }, window.location.origin);
    if (includeBoard) state.previewBoardDirty = false;
  }

  function postPreviewCommand(command) {
    if (!refs.runtimeFrame?.contentWindow) return;
    refs.runtimeFrame.contentWindow.postMessage({
      type: "dekpoke-mileage-preview-command",
      command
    }, window.location.origin);
  }

  function syncPreview() {
    if (!refs.previewStepRange || !state.board) return;
    refs.previewStepRange.max = String(Math.max(0, state.board.steps.length - 1));
    refs.previewStepRange.value = String(Editor.clamp(Number(state.previewStep ?? -1), -1, Math.max(0, state.board.steps.length - 1)));
    refs.previewStepLabel.textContent = Number(state.previewStep) < 0 ? "เริ่มต้น" : `ก้าว ${Math.round(Number(state.previewStep))}`;
    initPreviewRuntime();
    postPreviewBoard();
  }

  function stagePoint(event = null) {
    const nativeEvent = event?.evt || event || null;
    if (nativeEvent && typeof state.stage.setPointersPositions === "function") {
      state.stage.setPointersPositions(nativeEvent);
    }
    let pointer = state.stage.getPointerPosition();
    if (!pointer && nativeEvent) {
      const source = nativeEvent.touches?.[0] || nativeEvent.changedTouches?.[0] || nativeEvent;
      const rect = state.stage.container().getBoundingClientRect();
      pointer = {
        x: Number(source.clientX || 0) - rect.left,
        y: Number(source.clientY || 0) - rect.top
      };
    }
    if (!pointer) return state.lastPointer || { x: 0, y: 0 };
    const scale = state.stage.scaleX() || 1;
    const point = {
      x: (pointer.x - state.stage.x()) / scale,
      y: (pointer.y - state.stage.y()) / scale
    };
    state.lastPointer = point;
    return point;
  }

  function loadImage(src, onload) {
    const key = String(src || "").trim();
    if (!key) return null;
    const cached = state.imageCache.get(key);
    if (cached) {
      if (cached.status === "loaded") return cached.image;
      if (typeof onload === "function") cached.callbacks.push(onload);
      return null;
    }
    const image = new Image();
    image.__dekpokeCacheToken = `img${state.imageCache.size + 1}`;
    const entry = { status: "loading", image, callbacks: typeof onload === "function" ? [onload] : [] };
    image.onload = () => {
      entry.status = "loaded";
      entry.callbacks.splice(0).forEach((callback) => callback(image));
      drawBoard();
    };
    image.onerror = () => {
      entry.status = "error";
      entry.callbacks.splice(0);
      drawBoard();
    };
    image.src = key;
    state.imageCache.set(key, entry);
    return null;
  }

  function itemKey(type, index) {
    return `${type}:${index}`;
  }

  function spriteEdgeFadePx(sprite, width, height) {
    const fade = defaultSpriteEdgeFade(sprite?.src, plainObject(sprite?.meta) ? sprite.meta.edgeFade : sprite?.edgeFade);
    const maxFade = Math.max(0, Math.floor(Math.min(Math.max(1, width || 1), Math.max(1, height || 1)) / 2) - 1);
    return Math.min(Math.round(fade), maxFade);
  }

  function drawEdgeFadeMask(ctx, width, height, fadePx) {
    ctx.clearRect(0, 0, width, height);
    ctx.fillStyle = "#000";
    if (fadePx <= 0) {
      ctx.fillRect(0, 0, width, height);
      return;
    }

    const innerWidth = Math.max(0, width - (fadePx * 2));
    const innerHeight = Math.max(0, height - (fadePx * 2));
    if (innerWidth > 0 && innerHeight > 0) {
      ctx.fillRect(fadePx, fadePx, innerWidth, innerHeight);
    }

    if (innerWidth > 0) {
      let gradient = ctx.createLinearGradient(0, 0, 0, fadePx);
      gradient.addColorStop(0, "rgba(0,0,0,0)");
      gradient.addColorStop(1, "rgba(0,0,0,1)");
      ctx.fillStyle = gradient;
      ctx.fillRect(fadePx, 0, innerWidth, fadePx);

      gradient = ctx.createLinearGradient(0, height, 0, height - fadePx);
      gradient.addColorStop(0, "rgba(0,0,0,0)");
      gradient.addColorStop(1, "rgba(0,0,0,1)");
      ctx.fillStyle = gradient;
      ctx.fillRect(fadePx, height - fadePx, innerWidth, fadePx);
    }

    if (innerHeight > 0) {
      let gradient = ctx.createLinearGradient(0, 0, fadePx, 0);
      gradient.addColorStop(0, "rgba(0,0,0,0)");
      gradient.addColorStop(1, "rgba(0,0,0,1)");
      ctx.fillStyle = gradient;
      ctx.fillRect(0, fadePx, fadePx, innerHeight);

      gradient = ctx.createLinearGradient(width, 0, width - fadePx, 0);
      gradient.addColorStop(0, "rgba(0,0,0,0)");
      gradient.addColorStop(1, "rgba(0,0,0,1)");
      ctx.fillStyle = gradient;
      ctx.fillRect(width - fadePx, fadePx, fadePx, innerHeight);
    }

    [
      { cx: fadePx, cy: fadePx, x: 0, y: 0 },
      { cx: width - fadePx, cy: fadePx, x: width - fadePx, y: 0 },
      { cx: fadePx, cy: height - fadePx, x: 0, y: height - fadePx },
      { cx: width - fadePx, cy: height - fadePx, x: width - fadePx, y: height - fadePx }
    ].forEach((corner) => {
      const gradient = ctx.createRadialGradient(corner.cx, corner.cy, 0, corner.cx, corner.cy, fadePx);
      gradient.addColorStop(0, "rgba(0,0,0,1)");
      gradient.addColorStop(1, "rgba(0,0,0,0)");
      ctx.fillStyle = gradient;
      ctx.fillRect(corner.x, corner.y, fadePx, fadePx);
    });
  }

  function softSpriteFrame(image, box, width, height, fadePx) {
    const roundedWidth = Math.max(1, Math.round(width));
    const roundedHeight = Math.max(1, Math.round(height));
    const key = [
      image.__dekpokeCacheToken || "img",
      box.x,
      box.y,
      box.width,
      box.height,
      roundedWidth,
      roundedHeight,
      fadePx
    ].join(":");
    const cached = state.softSpriteCache.get(key);
    if (cached) return cached;

    const canvas = document.createElement("canvas");
    canvas.width = roundedWidth;
    canvas.height = roundedHeight;
    const drawCtx = canvas.getContext("2d");
    drawCtx.clearRect(0, 0, roundedWidth, roundedHeight);
    drawCtx.imageSmoothingEnabled = false;
    drawCtx.drawImage(image, box.x, box.y, box.width, box.height, 0, 0, roundedWidth, roundedHeight);

    const maskCanvas = document.createElement("canvas");
    maskCanvas.width = roundedWidth;
    maskCanvas.height = roundedHeight;
    const maskCtx = maskCanvas.getContext("2d");
    drawEdgeFadeMask(maskCtx, roundedWidth, roundedHeight, fadePx);

    drawCtx.globalCompositeOperation = "destination-in";
    drawCtx.drawImage(maskCanvas, 0, 0);
    drawCtx.globalCompositeOperation = "source-over";

    if (state.softSpriteCache.size > 320) {
      state.softSpriteCache.clear();
    }
    state.softSpriteCache.set(key, canvas);
    return canvas;
  }

  function spriteRenderSource(sprite, image, frame, width, height) {
    const box = frameBox(sprite, image, frame);
    const fadePx = spriteEdgeFadePx(sprite, width, height);
    if (fadePx <= 0) {
      return {
        image,
        crop: box
      };
    }
    return {
      image: softSpriteFrame(image, box, width, height, fadePx),
      crop: {
        x: 0,
        y: 0,
        width: Math.max(1, Math.round(width)),
        height: Math.max(1, Math.round(height))
      }
    };
  }

  function parseItemKey(key) {
    const [type, rawIndex] = String(key || "").split(":");
    const index = Number(rawIndex);
    return { type, index: Number.isInteger(index) ? index : -1 };
  }

  function selectedSingle() {
    if (state.selected.length !== 1) return null;
    return parseItemKey(state.selected[0]);
  }

  function isSelected(type, index) {
    return state.selected.includes(itemKey(type, index));
  }

  function selectItems(items, append = false) {
    const keys = items.map((item) => typeof item === "string" ? item : itemKey(item.type, item.index));
    state.selected = append ? Array.from(new Set([...state.selected, ...keys])) : Array.from(new Set(keys));
    syncTransformer();
    refreshUi();
  }

  function clearSelection() {
    state.selected = [];
    syncTransformer();
    refreshUi();
  }

  function syncTransformer() {
    if (!state.transformer) return;
    const nodes = state.selected
      .map((key) => state.stage.findOne(`.item-${key.replace(":", "-")}`))
      .filter((node) => node && ["sprite", "reward-node"].includes(String(node.getAttr("itemType") || "")));
    state.transformer.nodes(nodes);
    state.layers.ui.batchDraw();
  }

  function startMarqueeAtPointer(event = null) {
    const point = stagePoint(event);
    state.marquee = { start: point, current: point };
    state.selectionRect.visible(true);
    state.selectionRect.setAttrs({ x: point.x, y: point.y, width: 0, height: 0 });
    state.layers.ui.batchDraw();
  }

  function restoreGroupDraggable(group) {
    if (!group) return;
    const type = String(group.getAttr("itemType") || "");
    const index = Number(group.getAttr("itemIndex"));
    const entity = collectionForType(type)?.[index];
    group.draggable(entity?.locked !== true);
  }

  function updateGhostPreview(point = null) {
    if (!state.ghost || !state.stage) return;
    const pointer = point || state.lastPointer;
    const tool = state.tool;
    if (!pointer || !["step", "reward-node", "sprite", "segment"].includes(tool) || state.marquee) {
      state.ghost.visible(false);
      state.layers.ui.batchDraw();
      return;
    }
    state.ghost.destroyChildren();
    state.ghost.position(pointer);
    if (tool === "step") {
      state.ghost.add(new Konva.Circle({ radius: 13, fill: "rgba(247, 199, 107, 0.2)", stroke: "#f7c76b", strokeWidth: 2, dash: [6, 5] }));
      state.ghost.add(new Konva.Text({ x: -18, y: -33, width: 36, align: "center", text: String(state.board.steps.length), fontSize: 13, fontStyle: "bold", fill: "#f8fbff" }));
    } else if (tool === "reward-node") {
      state.ghost.add(new Konva.Circle({ radius: 22, fill: "rgba(121, 220, 207, 0.16)", stroke: "#79dccf", strokeWidth: 2, dash: [7, 5] }));
      state.ghost.add(new Konva.Text({ x: -12, y: -9, width: 24, align: "center", text: "R", fontSize: 16, fontStyle: "bold", fill: "#f8fbff" }));
    } else if (tool === "sprite") {
      state.ghost.add(new Konva.Rect({ x: -24, y: -24, width: 48, height: 48, fill: "rgba(142, 161, 255, 0.12)", stroke: "#8ea1ff", strokeWidth: 2, dash: [7, 5] }));
    } else if (tool === "segment") {
      const segment = segmentForY(pointer.y);
      if (segment) {
        state.ghost.position({ x: 0, y: Number(segment.y || 0) });
        state.ghost.add(new Konva.Rect({ x: 0, y: 0, width: boardWidth(), height: Math.max(1, Number(segment.h || 1)), fill: "rgba(142, 161, 255, 0.08)", stroke: "#8ea1ff", strokeWidth: 2, dash: [12, 7] }));
      }
    }
    state.ghost.visible(true);
    state.layers.ui.batchDraw();
  }

  function shapeBaseAttrs(type, index) {
    const key = itemKey(type, index);
    const entity = collectionForType(type)?.[index] || {};
    return {
      name: `editor-item item-${key.replace(":", "-")}`,
      itemType: type,
      itemIndex: index,
      draggable: entity.locked !== true
    };
  }

  function bindItemEvents(group) {
    group.on("mousedown touchstart", (event) => {
      event.cancelBubble = true;
      if (state.tool === "marquee" || event.evt.shiftKey) {
        group.draggable(false);
        state.marqueeDragNode = group;
        startMarqueeAtPointer(event);
        return;
      }
      state.dragStarted = true;
      const type = String(group.getAttr("itemType") || "");
      const index = Number(group.getAttr("itemIndex"));
      selectItems([{ type, index }], event.evt.shiftKey);
      if (state.tool === "simulate" && type === "reward-node") {
        previewRewardNode();
        setTool("select");
        return;
      }
      if (state.tool !== "select" && state.tool !== "marquee") {
        setTool("select");
      }
    });
    group.on("dragstart", () => {
      const type = String(group.getAttr("itemType") || "");
      const index = Number(group.getAttr("itemIndex"));
      const key = itemKey(type, index);
      if (!state.selected.includes(key)) {
        selectItems([{ type, index }]);
      }
      state.dragSnapshot = {
        key,
        start: { x: group.x(), y: group.y() },
        items: state.selected.map((selectedKey) => {
          const parsed = parseItemKey(selectedKey);
          const node = state.stage.findOne(`.item-${selectedKey.replace(":", "-")}`);
          return node ? { key: selectedKey, type: parsed.type, index: parsed.index, x: node.x(), y: node.y() } : null;
        }).filter(Boolean)
      };
    });
    group.on("dragmove", () => {
      const snapshot = state.dragSnapshot;
      const key = itemKey(String(group.getAttr("itemType") || ""), Number(group.getAttr("itemIndex")));
      if (!snapshot || snapshot.key !== key) return;
      const dx = group.x() - snapshot.start.x;
      const dy = group.y() - snapshot.start.y;
      snapshot.items.forEach((item) => {
        if (item.key === snapshot.key) return;
        const node = state.stage.findOne(`.item-${item.key.replace(":", "-")}`);
        if (node) node.position({ x: item.x + dx, y: item.y + dy });
      });
      snapshot.items.forEach((item) => {
        const node = state.stage.findOne(`.item-${item.key.replace(":", "-")}`);
        const itemEntity = collectionForType(item.type)?.[item.index];
        if (node && itemEntity && !itemEntity.locked) setEntityPoint(itemEntity, node.x(), node.y());
      });
      updateRouteLinesFromBoard();
      uniqueLayers().forEach((layer) => layer.batchDraw());
    });
    group.on("dragend", () => {
      const type = String(group.getAttr("itemType") || "");
      const index = Number(group.getAttr("itemIndex"));
      const entity = collectionForType(type)?.[index];
      if (entity?.locked) return;
      if (state.dragSnapshot?.items?.length) {
        state.dragSnapshot.items.forEach((item) => {
          const node = state.stage.findOne(`.item-${item.key.replace(":", "-")}`);
          const itemEntity = collectionForType(item.type)?.[item.index];
          if (node && !itemEntity?.locked) {
            commitGroupPosition(item.type, item.index, node.x(), node.y());
            node.scale({ x: 1, y: 1 });
          }
        });
      } else {
        commitGroupPosition(type, index, group.x(), group.y());
        group.scale({ x: 1, y: 1 });
      }
      state.dragSnapshot = null;
      setDirty(true);
      pushHistory();
      drawBoard();
    });
    group.on("transformend", () => {
      const type = String(group.getAttr("itemType") || "");
      const index = Number(group.getAttr("itemIndex"));
      commitGroupTransform(type, index, group);
      setDirty(true);
      pushHistory();
      drawBoard();
    });
  }

  function commitGroupPosition(type, index, x, y) {
    const collection = collectionForType(type);
    const entity = collection?.[index];
    if (!entity) return;
    setEntityPoint(entity, x, y);
  }

  function commitGroupTransform(type, index, group) {
    commitGroupPosition(type, index, group.x(), group.y());
    if (type === "sprite") {
      const sprite = state.board.sprites[index];
      if (!sprite) return;
      sprite.width = Math.max(1, Math.round(Number(sprite.width || 48) * Math.abs(group.scaleX() || 1)));
      sprite.height = Math.max(1, Math.round(Number(sprite.height || 48) * Math.abs(group.scaleY() || 1)));
    } else if (type === "reward-node") {
      const node = state.board.rewardNodes[index];
      const template = iconTemplateById(node?.iconTemplateId);
      if (template) {
        const nextScale = Number(template.scale || 1) * ((Math.abs(group.scaleX() || 1) + Math.abs(group.scaleY() || 1)) / 2);
        template.scale = Editor.clamp(nextScale, 0.1, 4);
      }
    }
    group.scale({ x: 1, y: 1 });
  }

  function collectionForType(type) {
    if (type === "step") return state.board.steps;
    if (type === "reward") return state.board.rewards;
    if (type === "sprite") return state.board.sprites;
    if (type === "reward-node") return state.board.rewardNodes;
    return null;
  }

  function iconTemplateById(id) {
    const target = String(id || "");
    return state.board.iconTemplates.find((template) => String(template.id || "") === target) || null;
  }

  function rewardTemplateById(id) {
    const target = String(id || "");
    return state.board.rewardTemplates.find((template) => String(template.id || "") === target) || null;
  }

  function selectedSegment() {
    return segmentById(state.selectedSegmentId);
  }

  function selectedIconTemplate() {
    return iconTemplateById(state.selectedIconTemplateId);
  }

  function selectedRewardTemplate() {
    return rewardTemplateById(state.selectedRewardTemplateId);
  }

  function drawBoard() {
    if (!state.stage || !state.board) return;
    normalizeBoard();
    const clearedLayers = new Set();
    Object.entries(state.layers).forEach(([key, layer]) => {
      if (!layer || key === "ui" || key === "simulation" || clearedLayers.has(layer)) return;
      layer.destroyChildren();
      clearedLayers.add(layer);
    });

    drawBackgroundLayer();
    drawSpriteLayer("decor-back");
    drawPathLayer();
    drawRewardLayer();
    drawSpriteLayer("decor-front");
    drawFxFrontLayer();
    state.transformer.moveToTop();
    state.selectionRect.moveToTop();
    syncTransformer();
    uniqueLayers().forEach((layer) => layer.batchDraw());
    refreshUi();
  }

  function drawBackgroundLayer() {
    const layer = state.layers.background;
    for (const segment of boardSegments()) {
      const image = loadImage(segment.src);
      if (image) {
        layer.add(new Konva.Image({
          x: 0,
          y: Number(segment.y || 0),
          width: boardWidth(),
          height: Math.max(1, Number(segment.h || 1)),
          image,
          listening: false
        }));
      } else {
        layer.add(new Konva.Rect({
          x: 0,
          y: Number(segment.y || 0),
          width: boardWidth(),
          height: Math.max(1, Number(segment.h || 1)),
          fill: "#101722",
          stroke: "#263242",
          listening: false
        }));
        layer.add(new Konva.Text({
          x: 20,
          y: Number(segment.y || 0) + 20,
          text: segment.src ? "กำลังโหลดพื้นหลัง" : "ยังไม่มีรูปพื้นหลัง",
          fill: "#98a5b8",
          fontSize: 22,
          listening: false
        }));
      }
    }
  }

  function drawPathLayer() {
    const layer = state.layers.path;
    const points = state.board.steps.filter((step) => step.visible !== false).flatMap((step) => {
      const p = pointOf(step);
      return [p.x, p.y];
    });
    if (points.length >= 4) {
      layer.add(new Konva.Line({
        name: "route-line route-shadow",
        points,
        stroke: "rgba(7, 10, 18, 0.72)",
        strokeWidth: 4,
        lineCap: "round",
        lineJoin: "round",
        listening: false
      }));
      layer.add(new Konva.Line({
        name: "route-line route-glow",
        points,
        stroke: "rgba(128, 239, 255, 0.84)",
        strokeWidth: 2,
        lineCap: "round",
        lineJoin: "round",
        shadowColor: "rgba(128, 239, 255, 0.5)",
        shadowBlur: 5,
        listening: false
      }));
    }
    const clustered = stepClusters();
    const clusteredIndexes = new Set(clustered.flatMap((cluster) => cluster.expanded ? [] : cluster.indexes));
    clustered.forEach((cluster) => {
      if (cluster.expanded || cluster.indexes.length <= 1) return;
      const group = new Konva.Group({
        ...shapeBaseAttrs("step", cluster.indexes[0]),
        name: `editor-item item-step-${cluster.indexes[0]} step-cluster-${cluster.key}`,
        x: cluster.point.x,
        y: cluster.point.y,
        clusterKey: cluster.key,
        clusterIndexes: cluster.indexes
      });
      const label = cluster.indexes.slice(0, 3).join(" | ") + (cluster.indexes.length > 3 ? "..." : "");
      const width = Math.max(46, label.length * 8 + 16);
      group.add(new Konva.Rect({
        x: -width / 2,
        y: -12,
        width,
        height: 24,
        cornerRadius: 12,
        fill: isSelected("step", cluster.indexes[0]) ? "#ffcf73" : "rgba(7, 11, 28, 0.74)",
        stroke: "#f5fbff",
        strokeWidth: 1.5
      }));
      group.add(new Konva.Text({
        text: label,
        x: -width / 2,
        y: -5,
        width,
        align: "center",
        fill: isSelected("step", cluster.indexes[0]) ? "#07101a" : "#f5fbff",
        fontSize: 10,
        fontStyle: "bold"
      }));
      group.on("dblclick dbltap", () => {
        state.expandedClusterKey = cluster.key;
        drawBoard();
      });
      bindItemEvents(group);
      layer.add(group);
    });
    state.board.steps.forEach((step, index) => {
      if (step.visible === false || clusteredIndexes.has(index)) return;
      const p = pointOf(step);
      const group = new Konva.Group({ ...shapeBaseAttrs("step", index), x: p.x, y: p.y });
      group.add(new Konva.Circle({
        radius: 15,
        fill: isSelected("step", index) ? "rgba(255, 207, 115, 0.78)" : "rgba(121, 220, 207, 0.68)",
        stroke: "rgba(7, 10, 18, 0.78)",
        strokeWidth: 2
      }));
      group.add(new Konva.Text({
        text: String(index),
        x: -24,
        y: -7,
        width: 48,
        align: "center",
        fill: "#07101a",
        fontSize: 14,
        fontStyle: "bold"
      }));
      bindItemEvents(group);
      layer.add(group);
    });
  }

  function updateRouteLinesFromBoard() {
    if (!state.layers.path || !state.board?.steps) return;
    const points = state.board.steps.filter((step) => step.visible !== false).flatMap((step) => {
      const p = pointOf(step);
      return [p.x, p.y];
    });
    state.layers.path.find(".route-line").forEach((line) => line.points(points));
    state.layers.path.batchDraw();
  }

  function stepClusters() {
    const scale = state.stage?.scaleX?.() || 1;
    const threshold = Math.max(14, 28 / Math.max(0.1, scale));
    const clusters = [];
    state.board.steps.forEach((step, index) => {
      if (step.visible === false) return;
      const point = pointOf(step);
      let cluster = clusters.find((item) => Math.hypot(item.point.x - point.x, item.point.y - point.y) <= threshold);
      if (!cluster) {
        cluster = { key: Editor.id("cluster"), point, indexes: [], expanded: false };
        clusters.push(cluster);
      }
      cluster.indexes.push(index);
      cluster.point = {
        x: cluster.indexes.reduce((sum, i) => sum + pointOf(state.board.steps[i]).x, 0) / cluster.indexes.length,
        y: cluster.indexes.reduce((sum, i) => sum + pointOf(state.board.steps[i]).y, 0) / cluster.indexes.length
      };
    });
    clusters.forEach((cluster) => {
      cluster.key = cluster.indexes.join("-");
      cluster.expanded = state.expandedClusterKey === cluster.key;
    });
    return clusters.filter((cluster) => cluster.indexes.length > 1);
  }

  function drawRewardLayer() {
    const layer = state.layers.reward;
    state.board.rewards.forEach((reward, index) => {
      if (reward.visible === false) return;
      const p = pointOf(reward);
      const size = Math.max(26, Number(state.board.meta?.ui?.rewardMarker?.size || 44));
      const group = new Konva.Group({ ...shapeBaseAttrs("reward", index), x: p.x, y: p.y });
      group.add(new Konva.Circle({
        radius: size / 2,
        fill: "rgba(8, 13, 22, 0.92)",
        stroke: rewardTone(reward.kind),
        strokeWidth: isSelected("reward", index) ? 5 : 3
      }));
      group.add(new Konva.RegularPolygon({
        sides: 4,
        radius: size * 0.24,
        fill: rewardTone(reward.kind),
        rotation: 45
      }));
      bindItemEvents(group);
      layer.add(group);
    });

    state.board.rewardNodes.forEach((node, index) => {
      if (node.visible === false) return;
      const p = pointOf(node);
      const group = new Konva.Group({ ...shapeBaseAttrs("reward-node", index), x: p.x, y: p.y });
      const template = iconTemplateById(node.iconTemplateId);
      const image = template ? loadImage(template.src) : null;
      const frameWidth = Math.max(1, Number(template?.frameWidth || image?.naturalWidth || 44));
      const frameHeight = Math.max(1, Number(template?.frameHeight || image?.naturalHeight || 44));
      const width = Math.max(24, frameWidth * Number(template?.scale || 1));
      const height = Math.max(24, frameHeight * Number(template?.scale || 1));
      if (image && template?.src) {
        const frame = spriteFrameIndex(template, performance.now());
        const box = frameBox(template, image, frame);
        group.add(new Konva.Image({
          name: "reward-node-image",
          image,
          x: -width * Number(template.anchorX ?? 0.5) + Number(template.offsetX || 0),
          y: -height * Number(template.anchorY ?? 0.5) + Number(template.offsetY || 0),
          width,
          height,
          crop: box,
          rewardNodeIndex: index
        }));
      } else {
        group.add(new Konva.Circle({
          radius: 24,
          fill: "rgba(247, 199, 107, 0.18)",
          stroke: "#f7c76b",
          strokeWidth: isSelected("reward-node", index) ? 4 : 2,
          dash: [8, 6]
        }));
        group.add(new Konva.Text({
          text: "gift",
          x: -22,
          y: -7,
          width: 44,
          align: "center",
          fill: "#f7c76b",
          fontSize: 11,
          fontStyle: "bold"
        }));
      }
      if (isSelected("reward-node", index)) {
        group.add(new Konva.Rect({
          x: -width / 2 - 5,
          y: -height / 2 - 5,
          width: width + 10,
          height: height + 10,
          stroke: "#8ea1ff",
          strokeWidth: 2,
          dash: [8, 6],
          listening: false
        }));
      }
      bindItemEvents(group);
      layer.add(group);
    });
  }

  function drawSpriteLayer(slot = "decor-back") {
    const layer = slot === "decor-front" ? state.layers.decorFront : state.layers.decorBack;
    state.board.sprites
      .map((sprite, index) => ({ sprite, index }))
      .filter(({ sprite }) => sprite.visible !== false && normalizeLayerSlot(sprite.layerSlot, "decor-back") === slot)
      .sort((a, b) => Number(a.sprite.zIndex || 0) - Number(b.sprite.zIndex || 0))
      .forEach(({ sprite, index }) => {
      const p = pointOf(sprite);
      const group = new Konva.Group({ ...shapeBaseAttrs("sprite", index), x: p.x, y: p.y });
      const image = loadImage(sprite.src);
      const config = activeSpriteConfig(sprite);
      const width = Math.max(1, Number(config.width || sprite.width || 48));
      const height = Math.max(1, Number(config.height || sprite.height || 48));
      if (image) {
        const frame = spriteFrameIndex(config, performance.now());
        const renderSource = spriteRenderSource(config, image, frame, width, height);
        group.add(new Konva.Image({
          name: "sprite-image",
          image: renderSource.image,
          x: -width / 2,
          y: -height / 2,
          width,
          height,
          opacity: Number(config.opacity ?? 1),
          crop: renderSource.crop,
          spriteIndex: index
        }));
      } else {
        group.add(new Konva.Rect({
          x: -width / 2,
          y: -height / 2,
          width,
          height,
          fill: "rgba(142, 161, 255, 0.12)",
          stroke: "#8ea1ff",
          strokeWidth: 2,
          dash: [8, 6]
        }));
      }
      if (isSelected("sprite", index)) {
        group.add(new Konva.Rect({
          x: -width / 2 - 5,
          y: -height / 2 - 5,
          width: width + 10,
          height: height + 10,
          stroke: "#8ea1ff",
          strokeWidth: 2,
          dash: [8, 6],
          listening: false
        }));
      }
      bindItemEvents(group);
      layer.add(group);
    });
  }

  function activeSpriteConfig(sprite) {
    const states = plainObject(sprite.states) ? sprite.states : {};
    const enabled = Array.isArray(sprite.enabledStates) ? sprite.enabledStates : ["idle"];
    const isEnabled = (name) => name === "idle" || enabled.includes(name);
    const stepIndex = Number(sprite.stepIndex ?? sprite.meta?.stepIndex ?? -1);
    const previewStep = Number(state.previewStep ?? -1);
    let config = states.idle || {};
    if (isEnabled("notReady") && states.notReady && stepIndex >= 0 && previewStep < stepIndex) config = states.notReady;
    if (isEnabled("ready") && states.ready && stepIndex >= 0 && previewStep >= stepIndex) config = states.ready;
    if (isEnabled("claimed") && states.claimed && sprite.meta?.previewClaimed) config = states.claimed;
    return { ...sprite, ...config, opacity: Number(sprite.meta?.editorOpacity ?? config.opacity ?? 1) };
  }

  function drawFxFrontLayer() {
    const layer = state.layers.fxFront;
    const fx = state.board.meta?.fx || {};
    const glow = Number(fx.pathGlow ?? 1);
    if (glow <= 0 || !state.board.steps.length) return;
    const selected = selectedSingle();
    const step = selected?.type === "step" ? state.board.steps[selected.index] : state.board.steps[0];
    const p = pointOf(step);
    layer.add(new Konva.Circle({
      x: p.x,
      y: p.y,
      radius: 46 * glow,
      fillRadialGradientStartPoint: { x: 0, y: 0 },
      fillRadialGradientStartRadius: 2,
      fillRadialGradientEndPoint: { x: 0, y: 0 },
      fillRadialGradientEndRadius: 46 * glow,
      fillRadialGradientColorStops: [0, "rgba(255, 247, 171, 0.34)", 0.6, "rgba(128, 239, 255, 0.14)", 1, "rgba(128, 239, 255, 0)"],
      listening: false
    }));
  }

  function rewardTone(kind) {
    return {
      coin: "#ffd581",
      ticket: "#9ae9ff",
      gem: "#ffd2ff",
      potion: "#9cffbf",
      item: "#b9a0ff"
    }[String(kind || "")] || "#f7c76b";
  }

  function frameBox(sprite, image, frame) {
    const columns = Math.max(1, Number(sprite?.columns || 1));
    const rows = Math.max(1, Number(sprite?.rows || 1));
    const frameWidth = Math.max(1, Number(sprite?.frameWidth || 0) || Math.floor(image.naturalWidth / columns));
    const frameHeight = Math.max(1, Number(sprite?.frameHeight || 0) || Math.floor(image.naturalHeight / rows));
    const col = frame % columns;
    const row = Math.floor(frame / columns) % rows;
    return {
      x: Math.min(image.naturalWidth - frameWidth, col * frameWidth + Number(sprite?.frameX || 0)),
      y: Math.min(image.naturalHeight - frameHeight, row * frameHeight + Number(sprite?.frameY || 0)),
      width: frameWidth,
      height: frameHeight
    };
  }

  function spriteFrameIndex(sprite, ts) {
    const frameCount = Math.max(1, Number(sprite?.frameCount || 1));
    const fps = Math.max(1, Number(sprite?.fps || 12));
    const frame = Math.floor((ts / 1000) * fps);
    const mode = String(sprite?.mode || "loop");
    if (mode === "static") return Editor.clamp(Math.round(Number(sprite?.frameIndex || 0)), 0, frameCount - 1);
    if (mode === "once") return Math.min(frameCount - 1, frame);
    if (mode === "pingpong" && frameCount > 1) {
      const cycle = frameCount * 2 - 2;
      const p = frame % cycle;
      return p < frameCount ? p : cycle - p;
    }
    return frame % frameCount;
  }

  function handlePointerDown(event) {
    if (!state.board) return;
    if (state.inspectorEditing) return;
    if (event?.evt?.button === 1) return;
    stagePoint(event);
    state.dragStarted = false;
    const wantsMarquee = state.tool === "marquee" || event.evt.shiftKey;
    if (state.tool === "pan" || state.spacePan) {
      state.stage.draggable(true);
      return;
    }
    if (wantsMarquee) {
      event.cancelBubble = true;
      startMarqueeAtPointer(event);
      return;
    }
  }

  function handlePointerMove(event) {
    if (!state.marquee) {
      updateGhostPreview();
      return;
    }
    const point = stagePoint(event);
    state.marquee.current = point;
    const left = Math.min(state.marquee.start.x, point.x);
    const top = Math.min(state.marquee.start.y, point.y);
    state.selectionRect.setAttrs({
      x: left,
      y: top,
      width: Math.abs(point.x - state.marquee.start.x),
      height: Math.abs(point.y - state.marquee.start.y)
    });
    state.layers.ui.batchDraw();
  }

  function handlePointerUp(event) {
    if (state.inspectorEditing) return;
    if (event?.evt?.button === 1) return;
    stagePoint(event);
    state.stage.draggable(state.tool === "pan" || state.spacePan);
    if (state.marquee) {
      commitMarqueeSelection(event.evt.shiftKey);
      state.marquee = null;
      state.selectionRect.visible(false);
      restoreGroupDraggable(state.marqueeDragNode);
      state.marqueeDragNode = null;
      updateGhostPreview();
      state.layers.ui.batchDraw();
      return;
    }
    restoreGroupDraggable(state.marqueeDragNode);
    state.marqueeDragNode = null;
    if (event.target !== state.stage) return;
    if (state.tool === "step") {
      addStepAt(stagePoint(event));
      if (!state.stickyTool) setTool("select");
    } else if (state.tool === "reward-node") {
      addRewardNodeAt(stagePoint(event));
      if (!state.stickyTool) setTool("select");
    } else if (state.tool === "sprite") {
      addSpriteAt(stagePoint(event));
      if (!state.stickyTool) setTool("select");
    } else if (state.tool === "segment") {
      const segment = segmentForY(stagePoint(event).y);
      state.selectedSegmentId = String(segment?.id || "");
      selectItems([]);
      refreshUi();
    } else if (state.tool === "select") {
      clearSelection();
    }
    updateGhostPreview();
  }

  function commitMarqueeSelection(append) {
    const rect = state.selectionRect.getClientRect({ relativeTo: state.stage });
    const hits = [];
    state.stage.find(".editor-item").forEach((node) => {
      const box = node.getClientRect({ relativeTo: state.stage });
      const overlap = !(box.x > rect.x + rect.width || box.x + box.width < rect.x || box.y > rect.y + rect.height || box.y + box.height < rect.y);
      if (overlap) {
        hits.push({ type: node.getAttr("itemType"), index: Number(node.getAttr("itemIndex")) });
      }
    });
    selectItems(hits, append);
  }

  function handleContextMenu(event) {
    event.evt.preventDefault();
    Editor.closeContextMenu();
    const boardPoint = stagePoint(event);
    const pointer = { x: event.evt.clientX, y: event.evt.clientY };
    const target = event.target;
    const itemNode = target?.findAncestor?.(".editor-item", true) || (target?.hasName?.("editor-item") ? target : null);
    const type = itemNode ? String(itemNode.getAttr("itemType") || "") : "";
    const index = itemNode ? Number(itemNode.getAttr("itemIndex")) : -1;
    const clusterKey = itemNode ? String(itemNode.getAttr("clusterKey") || "") : "";
    if (type && index >= 0) {
      selectItems([{ type, index }], event.evt.shiftKey);
    }
    const isStep = type === "step";
    const isSprite = type === "sprite";
    const entity = collectionForType(type)?.[index] || null;
    const items = [
      clusterKey && { label: "แยกกลุ่มเลข", icon: "fa-solid fa-up-right-and-down-left-from-center", action: () => { state.expandedClusterKey = clusterKey; drawBoard(); } },
      isStep && { label: "แทรกช่องก่อนหน้า", icon: "fa-solid fa-arrow-left", action: () => addStepAt(boardPoint, index) },
      isStep && { label: "แทรกช่องถัดไป", icon: "fa-solid fa-arrow-right", action: () => addStepAt(boardPoint, index + 1) },
      { label: "เพิ่มช่องท้ายสุด", icon: "fa-solid fa-location-dot", action: () => addStepAt(boardPoint, state.board.steps.length) },
      { label: "เพิ่มจุดรางวัล", icon: "fa-solid fa-gift", action: () => addRewardNodeAt(boardPoint) },
      { label: "วาง Sprite", icon: "fa-regular fa-image", action: () => addSpriteAt(boardPoint) },
      isSprite && { label: "พรีวิว Sprite", icon: "fa-solid fa-play", action: () => previewSelectedSprite() },
      isSprite && { label: "ส่งไปใต้ทางเดิน", icon: "fa-solid fa-arrow-down-short-wide", action: () => sendSelectedSpriteUnderPath() },
      entity && { label: "ขยับขึ้นหนึ่งชั้น", icon: "fa-solid fa-arrow-up", action: () => moveSelectedZ(1) },
      entity && { label: "ขยับลงหนึ่งชั้น", icon: "fa-solid fa-arrow-down", action: () => moveSelectedZ(-1) },
      entity && { label: "คัดลอกตำแหน่ง", icon: "fa-solid fa-location-crosshairs", action: () => copySelectedPosition() },
      entity && { label: entity.locked ? "ปลดล็อค" : "ล็อค", icon: entity.locked ? "fa-solid fa-lock-open" : "fa-solid fa-lock", action: () => toggleEntityLock(type, index) },
      entity && { label: entity.visible === false ? "แสดง" : "ซ่อน", icon: entity.visible === false ? "fa-regular fa-eye" : "fa-regular fa-eye-slash", action: () => toggleEntityVisible(type, index) },
      entity && { label: "ทำสำเนา", icon: "fa-solid fa-copy", action: duplicateSelected },
      entity && { label: "ลบ", icon: "fa-solid fa-trash", action: deleteSelected }
    ];
    Editor.openContextMenu(items, event.evt.clientX || pointer.x, event.evt.clientY || pointer.y);
  }

  function handleWheel(event) {
    event.evt.preventDefault();
    const oldScale = state.stage.scaleX() || 1;
    const pointer = state.stage.getPointerPosition();
    if (!pointer) return;
    const mousePointTo = {
      x: (pointer.x - state.stage.x()) / oldScale,
      y: (pointer.y - state.stage.y()) / oldScale
    };
    const direction = event.evt.deltaY > 0 ? -1 : 1;
    const scaleBy = 1.07;
    const newScale = Editor.clamp(direction > 0 ? oldScale * scaleBy : oldScale / scaleBy, 0.04, 3);
    state.stage.scale({ x: newScale, y: newScale });
    state.stage.position({
      x: pointer.x - mousePointTo.x * newScale,
      y: pointer.y - mousePointTo.y * newScale
    });
    state.stage.batchDraw();
    refreshStatus();
  }

  function addStepAt(point, insertAtOverride = null) {
    const step = { i: 0, label: "", meta: {} };
    setEntityPoint(step, point.x, point.y);
    const selectedStep = selectedSingle()?.type === "step" ? selectedSingle().index : -1;
    const insertAt = Number.isInteger(insertAtOverride)
      ? Editor.clamp(insertAtOverride, 0, state.board.steps.length)
      : selectedStep >= 0 ? selectedStep + 1 : state.board.steps.length;
    state.board.steps.splice(insertAt, 0, step);
    state.board.rewards.forEach((reward) => {
      if (Number.isInteger(reward.stepIndex) && reward.stepIndex >= insertAt) reward.stepIndex += 1;
    });
    normalizeBoard();
    selectItems([{ type: "step", index: insertAt }]);
    setDirty(true);
    pushHistory();
    drawBoard();
  }

  function nearestStepInsertIndex(point) {
    if (!point || !state.board?.steps?.length) return state.board?.steps?.length || 0;
    let best = null;
    state.board.steps.forEach((step, index) => {
      const p = pointOf(step);
      const distance = Math.hypot(point.x - p.x, point.y - p.y);
      if (!best || distance < best.distance) best = { index, point: p, distance };
    });
    if (!best || best.distance > 52) return state.board.steps.length;
    return point.y < best.point.y || point.x < best.point.x ? best.index : best.index + 1;
  }

  function toggleEntityLock(type, index) {
    const entity = collectionForType(type)?.[index];
    if (!entity) return;
    entity.locked = !entity.locked;
    setDirty(true);
    pushHistory();
    drawBoard();
  }

  function toggleEntityVisible(type, index) {
    const entity = collectionForType(type)?.[index];
    if (!entity) return;
    entity.visible = entity.visible === false;
    setDirty(true);
    pushHistory();
    drawBoard();
  }

  function addRewardNodeAt(point) {
    const node = {
      id: Editor.id("reward_node"),
      label: "",
      iconTemplateId: state.selectedIconTemplateId || String(state.board.iconTemplates[0]?.id || ""),
      rewardTemplateId: state.selectedRewardTemplateId || String(state.board.rewardTemplates[0]?.id || ""),
      stepIndex: selectedSingle()?.type === "step" ? selectedSingle().index : null,
      visible: true,
      locked: false,
      layerSlot: "reward",
      zIndex: state.board.rewardNodes.length,
      meta: { kind: "coin", amount: 1, itemCode: "" }
    };
    setEntityPoint(node, point.x, point.y);
    state.board.rewardNodes.push(node);
    normalizeBoard();
    selectItems([{ type: "reward-node", index: state.board.rewardNodes.length - 1 }]);
    setDirty(true);
    pushHistory();
    drawBoard();
  }

  function addSpriteAt(point, configOverride = null) {
    let config = {};
    try {
      config = JSON.parse(localStorage.getItem("mileageSpriteConfig") || "{}") || {};
    } catch (error) {
      config = {};
    }
    if (plainObject(configOverride)) config = { ...config, ...configOverride };
    const sprite = {
      id: Editor.id("sprite"),
      label: String(config.label || ""),
      src: String(config.src || ""),
      width: Math.max(1, Math.round(Number(config.width || config.frameWidth || 96))),
      height: Math.max(1, Math.round(Number(config.height || config.frameHeight || 96))),
      columns: Math.max(1, Math.round(Number(config.columns || 1))),
      rows: Math.max(1, Math.round(Number(config.rows || 1))),
      frameWidth: Math.max(0, Math.round(Number(config.frameWidth || 0))),
      frameHeight: Math.max(0, Math.round(Number(config.frameHeight || 0))),
      frameCount: Math.max(1, Math.round(Number(config.frameCount || 1))),
      fps: Editor.clamp(Number(config.fps || 12), 1, 60),
      mode: ["static", "once", "loop", "pingpong"].includes(String(config.mode || "")) ? config.mode : "loop",
      autoplay: true,
      visible: true,
      locked: false,
      layerSlot: "decor-back",
      zIndex: state.board.sprites.length,
      stepIndex: -1,
      enabledStates: ["idle"],
      states: {
        idle: {
          label: "วางเฉยๆ",
          frameIndex: 0,
          mode: String(config.mode || "") === "static" ? "static" : "loop",
          columns: Math.max(1, Math.round(Number(config.columns || 1))),
          rows: Math.max(1, Math.round(Number(config.rows || 1))),
          frameWidth: Math.max(0, Math.round(Number(config.frameWidth || 0))),
          frameHeight: Math.max(0, Math.round(Number(config.frameHeight || 0))),
          frameCount: Math.max(1, Math.round(Number(config.frameCount || 1))),
          fps: Editor.clamp(Number(config.fps || 12), 1, 60)
        }
      },
      meta: {
        editorOpacity: 1,
        edgeFade: defaultSpriteEdgeFade(String(config.src || ""), config.edgeFade)
      }
    };
    setEntityPoint(sprite, point.x, point.y);
    state.board.sprites.push(sprite);
    normalizeBoard();
    selectItems([{ type: "sprite", index: state.board.sprites.length - 1 }]);
    setDirty(true);
    pushHistory();
    drawBoard();
  }

  function setTool(tool) {
    state.tool = tool;
    refs.toolButtons.forEach((button) => button.classList.toggle("is-active", button.dataset.tool === tool));
    refs.activeToolLabel.textContent = toolLabel(tool);
    state.stage.draggable(tool === "pan" || state.spacePan);
    state.stage.container().style.cursor = editorCursor();
    updateGhostPreview();
    refreshUi();
  }

  function toolLabel(tool) {
    return {
      select: "เลือก",
      pan: "เลื่อน",
      marquee: "คลุมเลือก",
      step: "ช่องเดิน",
      "reward-node": "จุดรางวัล",
      sprite: "Sprite",
      segment: "พื้นหลัง"
    }[String(tool || "")] || "เลือก";
  }

  function setDirty(flag = true) {
    state.dirty = flag;
    if (flag) state.previewBoardDirty = true;
    refs.draftStatePill.textContent = flag ? "ร่างยังไม่บันทึก" : state.hasDraft ? "บันทึกร่างแล้ว" : "ข้อมูลจริง";
    refs.draftStatePill.classList.toggle("is-warn", flag);
    refs.draftStatePill.classList.toggle("is-good", !flag && state.hasDraft);
  }

  function pushHistory() {
    state.history.push(state.board);
  }

  function applySnapshot(snapshot) {
    if (!snapshot) return;
    state.board = Editor.deepClone(snapshot);
    normalizeBoard();
    setDirty(true);
    drawBoard();
  }

  function refreshHistoryButtons() {
    refs.undoButton.disabled = !state.history.canUndo();
    refs.redoButton.disabled = !state.history.canRedo();
  }

  function refreshUi() {
    if (!state.board) return;
    if (!state.inspectorEditing) {
      refreshSelectionInspector();
      refreshBoardInputs();
    }
    refreshLists();
    refreshLayerList();
    refreshStatus();
    renderAssetPreview();
    refs.jsonPreview.value = JSON.stringify(state.board, null, 2);
    refs.publishButton.disabled = !state.saveAllowed || Editor.hasDataUrl(state.board);
    refs.saveDraftButton.disabled = !state.saveAllowed;
  }

  function refreshBoardInputs() {
    refs.boardTitleInput.value = state.board.title || "";
    refs.rewardMarkerSizeInput.value = Math.round(Number(state.board.meta?.ui?.rewardMarker?.size || 44));
    refs.pickupScaleInput.value = Number(state.board.meta?.ui?.currencyPickup?.scale || 1.3);
    refs.pickupCountInput.value = Number(state.board.meta?.ui?.currencyPickup?.countMultiplier || 1.45);
    if (refs.fxPathGlowInput) refs.fxPathGlowInput.value = Number(state.board.meta?.fx?.pathGlow ?? 1);
    if (refs.fxCloudInput) refs.fxCloudInput.value = Number(state.board.meta?.fx?.clouds ?? 1);
    if (refs.fxFriendCountInput) refs.fxFriendCountInput.value = Number(state.board.meta?.fx?.friendCount ?? 3);
    rangeSyncInputs.forEach((range) => {
      const input = document.getElementById(range.dataset.rangeTarget || "");
      if (input) range.value = input.value;
    });
  }

  function refreshStatus() {
    refs.zoomLabel.textContent = `${Math.round((state.stage?.scaleX() || 1) * 100)}%`;
    refs.stepCountLabel.textContent = String(state.board?.steps?.length || 0);
    refs.rewardCountLabel.textContent = String(state.board?.rewards?.length || 0);
    refs.rewardNodeCountLabel.textContent = String(state.board?.rewardNodes?.length || 0);
    refs.spriteCountLabel.textContent = String(state.board?.sprites?.length || 0);
    refs.selectionPill.textContent = state.selected.length ? `เลือก ${state.selected.length} ชิ้น` : "ยังไม่เลือก";
    const hasData = state.board ? Editor.hasDataUrl(state.board) : false;
    refs.assetWarningLabel.textContent = hasData ? "เผยแพร่ไม่ได้: ยังมีรูปแบบ data:image ต้องอัปโหลดใหม่" : "";
    refs.assetWarningLabel.style.color = hasData ? "var(--editor-danger)" : "";
    syncPreview();
  }

  function refreshLists() {
    refs.segmentList.innerHTML = boardSegments().map((segment) => `
      <button class="editor-list-row${segment.id === state.selectedSegmentId ? " is-active" : ""}" type="button" data-segment-id="${Editor.escapeHtml(segment.id)}">
        <span>${Editor.escapeHtml(segment.id)}</span>
        <span class="editor-muted">y ${Math.round(segment.y)} / h ${Math.round(segment.h)}</span>
      </button>
    `).join("");
    refs.iconTemplateList.innerHTML = state.board.iconTemplates.map((template) => `
      <button class="editor-list-row is-draggable${template.id === state.selectedIconTemplateId ? " is-active" : ""}" type="button" draggable="true" data-icon-template-id="${Editor.escapeHtml(template.id)}">
        <span>${Editor.escapeHtml(template.label || template.id)}</span>
        <span class="editor-muted">${template.frameWidth || "-"}x${template.frameHeight || "-"}</span>
      </button>
    `).join("") || '<div class="editor-note">ยังไม่มีไอคอนรางวัล</div>';
    refs.rewardTemplateList.innerHTML = state.board.rewardTemplates.map((template) => `
      <button class="editor-list-row${template.id === state.selectedRewardTemplateId ? " is-active" : ""}" type="button" data-reward-template-id="${Editor.escapeHtml(template.id)}">
        <span>${Editor.escapeHtml(template.label || template.id)}</span>
        <span class="editor-muted">${Editor.escapeHtml(template.rewardTemplateId || "-")}</span>
      </button>
    `).join("") || '<div class="editor-note">ยังไม่มี template รางวัล</div>';
    const versions = Array.isArray(state.versions?.versions) ? state.versions.versions : [];
    refs.versionList.innerHTML = versions.slice(0, 10).map((version) => `
      <div class="editor-list-row">
        <span>
          <strong>${Editor.escapeHtml(version.id)}</strong><br>
          <span class="editor-muted">${Number(version.stepCount || 0)} steps / ${Number(version.rewardNodeCount || 0)} nodes</span>
        </span>
        <span class="editor-actions">
          <button class="ui mini button" type="button" data-version-draft="${Editor.escapeHtml(version.id)}">คืนร่าง</button>
          <button class="ui mini button" type="button" data-version-publish="${Editor.escapeHtml(version.id)}">เผยแพร่</button>
        </span>
      </div>
    `).join("") || '<div class="editor-note">ยังไม่มี version ที่เผยแพร่</div>';
  }

  function layerRows() {
    const rows = [];
    state.board.steps.forEach((item, index) => rows.push({ type: "step", index, item, label: `ช่องเดิน ${index}`, kind: "ทางเดิน" }));
    state.board.rewardNodes.forEach((item, index) => rows.push({ type: "reward-node", index, item, label: item.label || `จุดรางวัล ${index + 1}`, kind: "รางวัล" }));
    state.board.rewards.forEach((item, index) => rows.push({ type: "reward", index, item, label: item.label || `รางวัลเดิม ${index + 1}`, kind: "รางวัลเดิม" }));
    state.board.sprites.forEach((item, index) => rows.push({ type: "sprite", index, item, label: item.label || `Sprite ${index + 1}`, kind: layerSlotLabel(item.layerSlot) }));
    return rows.sort((a, b) => {
      const slotWeight = { "decor-front": 5, reward: 4, path: 3, "decor-back": 2, background: 1, "fx-front": 6 };
      return (slotWeight[b.item.layerSlot] || 0) - (slotWeight[a.item.layerSlot] || 0) || Number(b.item.zIndex || 0) - Number(a.item.zIndex || 0);
    });
  }

  function layerSlotLabel(slot) {
    return {
      background: "พื้นหลัง",
      "decor-back": "ใต้ทางเดิน",
      path: "ทางเดิน",
      reward: "รางวัล",
      "decor-front": "ด้านหน้า",
      "fx-front": "เอฟเฟคหน้า"
    }[String(slot || "")] || "ใต้ทางเดิน";
  }

  function refreshLayerList() {
    if (!refs.layerList) return;
    const rows = layerRows();
    refs.layerCountLabel.textContent = String(rows.length);
    refs.layerList.innerHTML = rows.map((row) => {
      const key = itemKey(row.type, row.index);
      return `
        <div class="editor-layer-row${isSelected(row.type, row.index) ? " is-active" : ""}${row.item.visible === false ? " is-muted" : ""}" draggable="true" data-layer-type="${Editor.escapeHtml(row.type)}" data-layer-index="${row.index}" data-layer-key="${Editor.escapeHtml(key)}">
          <span class="editor-layer-handle" data-tip="ลากเพื่อเรียงลำดับ"><i class="fa-solid fa-grip-vertical"></i></span>
          <button class="ui mini icon button" type="button" data-layer-action="visible" data-tip="${row.item.visible === false ? "แสดง" : "ซ่อน"}"><i class="${row.item.visible === false ? "fa-regular fa-eye-slash" : "fa-regular fa-eye"}"></i></button>
          <button class="ui mini icon button" type="button" data-layer-action="lock" data-tip="${row.item.locked ? "ปลดล็อค" : "ล็อค"}"><i class="${row.item.locked ? "fa-solid fa-lock" : "fa-solid fa-lock-open"}"></i></button>
          <span class="editor-layer-name">${Editor.escapeHtml(row.label)}<span class="editor-layer-kind">${Editor.escapeHtml(row.kind)}</span></span>
          <button class="ui mini icon button" type="button" data-layer-action="select" data-tip="เลือกชิ้นนี้"><i class="fa-solid fa-crosshairs"></i></button>
        </div>
      `;
    }).join("") || '<div class="editor-note">ยังไม่มี object บนแผนที่</div>';
  }

  function layerRowByKey(key) {
    const [type, rawIndex] = String(key || "").split(":");
    const index = Number(rawIndex);
    const item = collectionForType(type)?.[index];
    return item ? { type, index, item } : null;
  }

  function reorderLayerRows(dragKey, targetKey, placeAfter = false) {
    if (!dragKey || !targetKey || dragKey === targetKey) return;
    const rows = layerRows().map((row) => ({ ...row, key: itemKey(row.type, row.index) }));
    const from = rows.findIndex((row) => row.key === dragKey);
    const to = rows.findIndex((row) => row.key === targetKey);
    if (from < 0 || to < 0) return;
    const [dragged] = rows.splice(from, 1);
    const nextTo = rows.findIndex((row) => row.key === targetKey);
    rows.splice(nextTo + (placeAfter ? 1 : 0), 0, dragged);
    const target = layerRowByKey(targetKey);
    if (target) dragged.item.layerSlot = normalizeLayerSlot(target.item.layerSlot, dragged.item.layerSlot || "decor-back");
    const grouped = new Map();
    rows.forEach((row) => {
      const slot = normalizeLayerSlot(row.item.layerSlot, "decor-back");
      if (!grouped.has(slot)) grouped.set(slot, []);
      grouped.get(slot).push(row);
    });
    grouped.forEach((slotRows) => {
      slotRows.forEach((row, visualIndex) => {
        row.item.zIndex = slotRows.length - visualIndex;
      });
    });
    normalizeBoard();
    setDirty(true);
    pushHistory();
    drawBoard();
  }

  function clearLayerDropState() {
    refs.layerList?.querySelectorAll(".editor-layer-row").forEach((item) => {
      item.classList.remove("is-drop-before", "is-drop-after");
    });
  }

  function autoScrollLayerPanel(clientY) {
    const panelBody = refs.layerList?.closest(".editor-panel-body");
    if (!panelBody) return;
    const rect = panelBody.getBoundingClientRect();
    if (clientY < rect.top + 46) panelBody.scrollTop -= 18;
    if (clientY > rect.bottom - 46) panelBody.scrollTop += 18;
  }

  function updateLayerPointerDrag(event) {
    if (!state.layerPointerDrag || !refs.layerList) return;
    event.preventDefault();
    autoScrollLayerPanel(event.clientY);
    const element = document.elementFromPoint(event.clientX, event.clientY);
    const row = element?.closest?.("#layerList [data-layer-key]");
    clearLayerDropState();
    state.layerPointerDrag.targetKey = "";
    state.layerPointerDrag.placeAfter = false;
    if (!row || row.dataset.layerKey === state.layerPointerDrag.dragKey) return;
    const rect = row.getBoundingClientRect();
    const placeAfter = event.clientY > rect.top + rect.height / 2;
    row.classList.add(placeAfter ? "is-drop-after" : "is-drop-before");
    state.layerPointerDrag.targetKey = row.dataset.layerKey || "";
    state.layerPointerDrag.placeAfter = placeAfter;
  }

  function finishLayerPointerDrag(event) {
    if (!state.layerPointerDrag || !refs.layerList) return;
    updateLayerPointerDrag(event);
    const { dragKey, targetKey, placeAfter } = state.layerPointerDrag;
    state.layerPointerDrag = null;
    refs.layerList.querySelectorAll(".editor-layer-row").forEach((item) => {
      item.classList.remove("is-dragging", "is-drop-before", "is-drop-after");
    });
    document.removeEventListener("pointermove", updateLayerPointerDrag, true);
    document.removeEventListener("pointerup", finishLayerPointerDrag, true);
    if (targetKey && targetKey !== dragKey) reorderLayerRows(dragKey, targetKey, placeAfter);
  }

  function refreshSelectionInspector() {
    const selected = selectedSingle();
    if (!selected) {
      if (state.selectedIconTemplateId && selectedIconTemplate()) {
        renderIconTemplateInspector();
        return;
      }
      if (state.selectedRewardTemplateId && selectedRewardTemplate()) {
        renderRewardTemplateInspector();
        return;
      }
      if (state.selectedSegmentId && selectedSegment()) {
        renderSegmentInspector();
        return;
      }
      refs.selectionInspector.innerHTML = `
        <p class="editor-note">ใช้เครื่องมือเลือกเพื่อจับ ย้าย ปรับขนาด หรือคลุมหลายชิ้น เครื่องมือวางจะกลับมาเป็นเลือกหลังวางเสร็จ</p>
        ${state.tool === "sprite" ? '<p class="editor-note">Sprite จะใช้ config ล่าสุดที่ส่งมาจากหน้า Sprite ถ้ามี</p>' : ""}
      `;
      return;
    }
    if (selected.type === "step") renderStepInspector(selected.index);
    else if (selected.type === "reward") renderRewardInspector(selected.index);
    else if (selected.type === "reward-node") renderRewardNodeInspector(selected.index);
    else if (selected.type === "sprite") renderSpriteInspector(selected.index);
    else refs.selectionInspector.innerHTML = "";
  }

  function renderSegmentInspector() {
    const segment = selectedSegment();
    if (!segment) return;
    refs.selectionInspector.innerHTML = `
      <div class="editor-field"><label>พื้นหลัง</label><span class="editor-muted">y ${Math.round(segment.y || 0)}</span></div>
      <label class="editor-field">ID <input data-edit="segment.id" type="text" value="${Editor.escapeHtml(segment.id || "")}"></label>
      <label class="editor-field">Path รูป <input data-edit="segment.src" type="text" value="${Editor.escapeHtml(segment.src || "")}"></label>
      <div class="editor-actions" style="margin-bottom:8px">
        <label class="ui tiny button" for="segmentUploadInput"><i class="fa-solid fa-upload"></i> อัปโหลดพื้นหลัง</label>
        <button class="ui tiny button" data-action="pick-asset-segment" type="button"><i class="fa-solid fa-folder-open"></i> เลือกจากคลัง</button>
        <input id="segmentUploadInput" class="editor-hidden" type="file" accept="image/png,image/jpeg,image/webp,image/gif">
      </div>
      <div class="editor-grid-2">
        <label class="editor-field">ความสูง <input data-edit="segment.h" type="number" min="1" step="1" value="${Math.round(segment.h || 1)}"></label>
        <label class="editor-field">ความกว้างแผนที่ <input data-edit="board.width" type="number" min="1" step="1" value="${Math.round(boardWidth())}"></label>
      </div>
      <div class="editor-actions">
        <button class="ui tiny negative button" data-action="delete-segment" type="button"><i class="fa-solid fa-trash"></i></button>
      </div>
    `;
    document.getElementById("segmentUploadInput")?.addEventListener("change", handleSegmentUpload);
  }

  function renderIconTemplateInspector() {
    const template = selectedIconTemplate();
    if (!template) return;
    refs.selectionInspector.innerHTML = `
      <div class="editor-field"><label>ไอคอนรางวัล</label><span class="editor-muted">${Editor.escapeHtml(template.id)}</span></div>
      <label class="editor-field">ID <input data-edit="iconTemplate.id" type="text" value="${Editor.escapeHtml(template.id || "")}"></label>
      <label class="editor-field">ชื่อ <input data-edit="iconTemplate.label" type="text" value="${Editor.escapeHtml(template.label || "")}"></label>
      <label class="editor-field">Path รูป <input data-edit="iconTemplate.src" type="text" value="${Editor.escapeHtml(template.src || "")}"></label>
      <div class="editor-actions" style="margin-bottom:8px">
        <label class="ui tiny button" for="iconTemplateUploadInput"><i class="fa-solid fa-upload"></i> แทนที่</label>
        <button class="ui tiny button" data-action="pick-asset-icon" type="button"><i class="fa-solid fa-folder-open"></i> เลือกจากคลัง</button>
        <button class="ui tiny button" data-action="drag-icon-template" type="button"><i class="fa-solid fa-hand-pointer"></i> ลากใช้</button>
        <input id="iconTemplateUploadInput" class="editor-hidden" type="file" accept="image/png,image/jpeg,image/webp,image/gif">
      </div>
      <div class="editor-grid-2">
        <label class="editor-field">Frame X <input data-edit="iconTemplate.frameX" type="number" min="0" step="1" value="${Math.round(template.frameX || 0)}"></label>
        <label class="editor-field">Frame Y <input data-edit="iconTemplate.frameY" type="number" min="0" step="1" value="${Math.round(template.frameY || 0)}"></label>
        <label class="editor-field">Frame W <input data-edit="iconTemplate.frameWidth" type="number" min="0" step="1" value="${Math.round(template.frameWidth || 0)}"></label>
        <label class="editor-field">Frame H <input data-edit="iconTemplate.frameHeight" type="number" min="0" step="1" value="${Math.round(template.frameHeight || 0)}"></label>
        <label class="editor-field">Columns <input data-edit="iconTemplate.columns" type="number" min="1" step="1" value="${Math.round(template.columns || 1)}"></label>
        <label class="editor-field">Rows <input data-edit="iconTemplate.rows" type="number" min="1" step="1" value="${Math.round(template.rows || 1)}"></label>
        <label class="editor-field">Frames <input data-edit="iconTemplate.frameCount" type="number" min="1" step="1" value="${Math.round(template.frameCount || 1)}"></label>
        <label class="editor-field">FPS <input data-edit="iconTemplate.fps" type="number" min="1" max="60" step="1" value="${Number(template.fps || 12)}"></label>
        <label class="editor-field">สเกล <input data-edit="iconTemplate.scale" type="number" min="0.1" max="4" step="0.05" value="${Number(template.scale || 1)}"></label>
        <label class="editor-field">Offset X <input data-edit="iconTemplate.offsetX" type="number" step="1" value="${Number(template.offsetX || 0)}"></label>
        <label class="editor-field">Offset Y <input data-edit="iconTemplate.offsetY" type="number" step="1" value="${Number(template.offsetY || 0)}"></label>
        <label class="editor-field">Anchor X <input data-edit="iconTemplate.anchorX" type="number" min="0" max="1" step="0.05" value="${Number(template.anchorX ?? 0.5)}"></label>
        <label class="editor-field">Anchor Y <input data-edit="iconTemplate.anchorY" type="number" min="0" max="1" step="0.05" value="${Number(template.anchorY ?? 0.5)}"></label>
      </div>
      <label class="editor-field">ค่าเริ่มต้นแอนิเมชัน
        <select data-edit="iconTemplate.mode">
          ${["loop", "once", "pingpong"].map((mode) => `<option value="${mode}"${template.mode === mode ? " selected" : ""}>${mode}</option>`).join("")}
        </select>
      </label>
      <div class="editor-actions">
        <button class="ui tiny negative button" data-action="delete-icon-template" type="button"><i class="fa-solid fa-trash"></i></button>
      </div>
    `;
    document.getElementById("iconTemplateUploadInput")?.addEventListener("change", handleSelectedIconUpload);
  }

  function renderRewardTemplateInspector() {
    const template = selectedRewardTemplate();
    if (!template) return;
    refs.selectionInspector.innerHTML = `
      <div class="editor-field"><label>Template รางวัล</label><span class="editor-muted">${Editor.escapeHtml(template.id)}</span></div>
      <label class="editor-field">ID <input data-edit="rewardTemplate.id" type="text" value="${Editor.escapeHtml(template.id || "")}"></label>
      <label class="editor-field">ชื่อ <input data-edit="rewardTemplate.label" type="text" value="${Editor.escapeHtml(template.label || "")}"></label>
      <label class="editor-field">รหัส template จาก service <input data-edit="rewardTemplate.rewardTemplateId" type="text" value="${Editor.escapeHtml(template.rewardTemplateId || "")}"></label>
      <label class="editor-field">โหมด
        <select data-edit="rewardTemplate.mode">
          ${["fixed", "random"].map((mode) => `<option value="${mode}"${template.mode === mode ? " selected" : ""}>${mode}</option>`).join("")}
        </select>
      </label>
      <div class="editor-grid-2">
        <label class="editor-field">ชนิด <input data-edit="rewardTemplate.meta.kind" type="text" value="${Editor.escapeHtml(template.meta?.kind || "")}"></label>
        <label class="editor-field">จำนวน <input data-edit="rewardTemplate.meta.amount" type="number" min="0" step="1" value="${Number(template.meta?.amount || 0)}"></label>
      </div>
      <label class="editor-field">รหัสไอเทม <input data-edit="rewardTemplate.meta.itemCode" type="text" value="${Editor.escapeHtml(template.meta?.itemCode || "")}"></label>
      <div class="editor-actions">
        <button class="ui tiny negative button" data-action="delete-reward-template" type="button"><i class="fa-solid fa-trash"></i></button>
      </div>
    `;
  }

  function renderStepInspector(index) {
    const step = state.board.steps[index];
    if (!step) return;
    const p = pointOf(step);
    refs.selectionInspector.innerHTML = `
      <div class="editor-field"><label>ช่องเดิน #${index}</label></div>
      ${layerInspectorFields(step)}
      <label class="editor-field">ชื่อ <input data-edit="step.label" type="text" value="${Editor.escapeHtml(step.label || "")}"></label>
      <div class="editor-grid-2">
        <label class="editor-field">X px <input data-edit="entity.xpx" type="number" step="1" value="${Math.round(p.x)}"></label>
        <label class="editor-field">Y px <input data-edit="entity.ypx" type="number" step="1" value="${Math.round(p.y)}"></label>
      </div>
      <div class="editor-actions">
        <button class="ui tiny button" data-action="insert-step-before" type="button"><i class="fa-solid fa-arrow-left"></i> แทรกก่อน</button>
        <button class="ui tiny button" data-action="insert-step-after" type="button"><i class="fa-solid fa-arrow-right"></i> แทรกหลัง</button>
        <button class="ui tiny button" data-action="duplicate-step" type="button"><i class="fa-solid fa-copy"></i> ทำสำเนา</button>
        <button class="ui tiny negative button" data-action="delete-selected" type="button"><i class="fa-solid fa-trash"></i></button>
      </div>
    `;
  }

  function renderRewardInspector(index) {
    const reward = state.board.rewards[index];
    if (!reward) return;
    refs.selectionInspector.innerHTML = `
      <div class="editor-field"><label>รางวัลแบบเดิม</label></div>
      ${layerInspectorFields(reward)}
      <label class="editor-field">ชนิด
        <select data-edit="reward.kind">
          ${["coin", "ticket", "gem", "potion", "item"].map((kind) => `<option value="${kind}"${reward.kind === kind ? " selected" : ""}>${kind}</option>`).join("")}
        </select>
      </label>
      <label class="editor-field">จำนวน <input data-edit="reward.amount" type="number" min="1" step="1" value="${Number(reward.amount || 1)}"></label>
      <label class="editor-field">Template รางวัล <input data-edit="reward.rewardTemplateId" type="text" value="${Editor.escapeHtml(reward.rewardTemplateId || "")}"></label>
      <div class="editor-actions">
        <button class="ui tiny negative button" data-action="delete-selected" type="button"><i class="fa-solid fa-trash"></i></button>
      </div>
    `;
  }

  function renderRewardNodeInspector(index) {
    const node = state.board.rewardNodes[index];
    if (!node) return;
    const iconOptions = ['<option value="">ไม่มีไอคอน</option>'].concat(state.board.iconTemplates.map((template) => `<option value="${Editor.escapeHtml(template.id)}"${node.iconTemplateId === template.id ? " selected" : ""}>${Editor.escapeHtml(template.label || template.id)}</option>`)).join("");
    const rewardOptions = ['<option value="">ไม่มี template</option>'].concat(state.board.rewardTemplates.map((template) => `<option value="${Editor.escapeHtml(template.id)}"${node.rewardTemplateId === template.id ? " selected" : ""}>${Editor.escapeHtml(template.label || template.id)}</option>`)).join("");
    const p = pointOf(node);
    const maxStep = Math.max(0, state.board.steps.length - 1);
    refs.selectionInspector.innerHTML = `
      <div class="editor-field"><label>จุดรางวัล</label><span class="editor-muted">${Editor.escapeHtml(node.id)}</span></div>
      ${layerInspectorFields(node)}
      <label class="editor-field">ชื่อ <input data-edit="rewardNode.label" type="text" value="${Editor.escapeHtml(node.label || "")}"></label>
      <div class="editor-grid-2">
        ${rangeField("ปลดล็อคเมื่อถึงช่อง #", "rewardNode.stepIndex", node.stepIndex ?? -1, -1, maxStep, 1)}
        <label class="editor-field">ชนิด <input data-edit="rewardNode.meta.kind" type="text" value="${Editor.escapeHtml(node.meta?.kind || "coin")}"></label>
        <label class="editor-field">จำนวน <input data-edit="rewardNode.meta.amount" type="number" min="1" step="1" value="${Number(node.meta?.amount || 1)}"></label>
        <label class="editor-field">ไอเทม <input data-edit="rewardNode.meta.itemCode" type="text" value="${Editor.escapeHtml(node.meta?.itemCode || "")}"></label>
      </div>
      <label class="editor-field">ไอคอน <select data-edit="rewardNode.iconTemplateId">${iconOptions}</select></label>
      <label class="editor-field">Template รางวัล <select data-edit="rewardNode.rewardTemplateId">${rewardOptions}</select></label>
      <div class="editor-grid-2">
        <label class="editor-field">X px <input data-edit="entity.xpx" type="number" step="1" value="${Math.round(p.x)}"></label>
        <label class="editor-field">Y px <input data-edit="entity.ypx" type="number" step="1" value="${Math.round(p.y)}"></label>
      </div>
      <div class="editor-actions">
        <button class="ui tiny button" data-action="preview-reward-node" type="button"><i class="fa-solid fa-play"></i> ทดลองกด</button>
        <button class="ui tiny button" data-action="duplicate-reward-node" type="button"><i class="fa-solid fa-copy"></i></button>
        <button class="ui tiny negative button" data-action="delete-selected" type="button"><i class="fa-solid fa-trash"></i></button>
      </div>
    `;
  }

  function rangeField(label, path, value, min, max, step = 1) {
    const safeLabel = Editor.escapeHtml(label);
    const safePath = Editor.escapeHtml(path);
    const numeric = Number.isFinite(Number(value)) ? Number(value) : Number(min || 0);
    const safeValue = Editor.escapeHtml(String(numeric));
    return `
      <label class="editor-field editor-range-field">${safeLabel}
        <input data-edit="${safePath}" type="number" min="${Number(min)}" max="${Number(max)}" step="${Number(step)}" value="${safeValue}">
        <input data-edit="${safePath}" type="range" min="${Number(min)}" max="${Number(max)}" step="${Number(step)}" value="${safeValue}">
      </label>
    `;
  }

  function renderSpriteInspector(index) {
    const sprite = state.board.sprites[index];
    if (!sprite) return;
    const p = pointOf(sprite);
    const overlapWarning = spritePathOverlap(sprite) && normalizeLayerSlot(sprite.layerSlot, "decor-back") === "decor-front";
    const maxFrame = Math.max(0, Math.round(Number(sprite.frameCount || 1)) - 1);
    const maxStep = Math.max(0, state.board.steps.length - 1);
    const fadeMax = Math.max(12, Math.round(Math.min(Math.max(1, Number(sprite.width || 48)), Math.max(1, Number(sprite.height || 48))) / 3));
    refs.selectionInspector.innerHTML = `
      <div class="editor-field"><label>Sprite</label><span class="editor-muted">${Editor.escapeHtml(sprite.id)}</span></div>
      ${overlapWarning ? '<section class="editor-warning-box" style="margin-bottom:8px"><i class="fa-solid fa-triangle-exclamation"></i> Sprite นี้อยู่ด้านหน้าและทับทางเดิน อาจบังแสงเดิน <button class="ui mini button" data-action="send-sprite-under-path" type="button">ส่งไปใต้ทางเดิน</button></section>' : ""}
      ${layerInspectorFields(sprite)}
      <div class="editor-preview-small"><canvas id="selectedSpritePreview" width="160" height="120"></canvas></div>
      <label class="editor-field">ชื่อ <input data-edit="sprite.label" type="text" value="${Editor.escapeHtml(sprite.label || "")}"></label>
      <label class="editor-field">Path รูป <input data-edit="sprite.src" type="text" value="${Editor.escapeHtml(sprite.src || "")}"></label>
      <div class="editor-actions" style="margin-bottom:8px">
        <label class="ui tiny button" for="spriteUploadInput"><i class="fa-solid fa-upload"></i> อัปโหลด Sprite</label>
        <button class="ui tiny button" data-action="pick-asset-sprite" type="button"><i class="fa-solid fa-folder-open"></i> เลือกจากคลัง</button>
        <input id="spriteUploadInput" class="editor-hidden" type="file" accept="image/png,image/jpeg,image/webp,image/gif">
        <button class="ui tiny button${state.spriteRatioLocked ? " primary" : ""}" data-action="toggle-sprite-ratio" type="button"><i class="fa-solid fa-link"></i> ล็อคสัดส่วน</button>
      </div>
      <div class="editor-grid-2">
        <label class="editor-field">Show W <input data-edit="sprite.width" type="number" min="1" step="1" value="${Math.round(sprite.width)}"></label>
        <label class="editor-field">Show H <input data-edit="sprite.height" type="number" min="1" step="1" value="${Math.round(sprite.height)}"></label>
        <label class="editor-field">Columns <input data-edit="sprite.columns" type="number" min="1" step="1" value="${Math.round(sprite.columns)}"></label>
        <label class="editor-field">Rows <input data-edit="sprite.rows" type="number" min="1" step="1" value="${Math.round(sprite.rows)}"></label>
        <label class="editor-field">Frame W <input data-edit="sprite.frameWidth" type="number" min="0" step="1" value="${Math.round(sprite.frameWidth || 0)}"></label>
        <label class="editor-field">Frame H <input data-edit="sprite.frameHeight" type="number" min="0" step="1" value="${Math.round(sprite.frameHeight || 0)}"></label>
        ${rangeField("Frames", "sprite.frameCount", Math.round(sprite.frameCount || 1), 1, Math.max(1, Number(sprite.columns || 1) * Number(sprite.rows || 1)), 1)}
        ${rangeField("FPS", "sprite.fps", Number(sprite.fps || 12), 1, 60, 1)}
        ${rangeField("เปลี่ยนสถานะเมื่อถึงช่อง #", "sprite.stepIndex", Number(sprite.stepIndex ?? -1), -1, maxStep, 1)}
        ${rangeField("โปร่งแสงช่วยวาง", "sprite.meta.editorOpacity", Number(sprite.meta?.editorOpacity ?? 1), 0.1, 1, 0.05)}
        ${rangeField("เฟดขอบ px", "sprite.meta.edgeFade", Number(sprite.meta?.edgeFade ?? defaultSpriteEdgeFade(sprite.src)), 0, fadeMax, 1)}
        <label class="editor-field">X px <input data-edit="entity.xpx" type="number" step="1" value="${Math.round(p.x)}"></label>
        <label class="editor-field">Y px <input data-edit="entity.ypx" type="number" step="1" value="${Math.round(p.y)}"></label>
      </div>
      <label class="editor-field">ค่าเริ่มต้นของแอนิเมชัน
        <select data-edit="sprite.mode">
          ${["static", "loop", "once", "pingpong"].map((mode) => `<option value="${mode}"${sprite.mode === mode ? " selected" : ""}>${mode}</option>`).join("")}
        </select>
      </label>
      ${renderSpriteStateFields(sprite)}
      <div class="editor-actions">
        <button class="ui tiny button" data-action="duplicate-sprite" type="button"><i class="fa-solid fa-copy"></i></button>
        <button class="ui tiny negative button" data-action="delete-selected" type="button"><i class="fa-solid fa-trash"></i></button>
      </div>
    `;
    document.getElementById("spriteUploadInput")?.addEventListener("change", handleSpriteUpload);
    drawSelectedSpritePreview(sprite);
  }

  function distanceToSegment(point, start, end) {
    const vx = end.x - start.x;
    const vy = end.y - start.y;
    const lengthSq = vx * vx + vy * vy;
    if (lengthSq <= 0.0001) return Math.hypot(point.x - start.x, point.y - start.y);
    const t = Editor.clamp(((point.x - start.x) * vx + (point.y - start.y) * vy) / lengthSq, 0, 1);
    return Math.hypot(point.x - (start.x + vx * t), point.y - (start.y + vy * t));
  }

  function spritePathOverlap(sprite) {
    if (!sprite || !Array.isArray(state.board?.steps) || state.board.steps.length < 2) return false;
    const point = pointOf(sprite);
    const config = activeSpriteConfig(sprite);
    const radius = Math.max(Number(config.width || sprite.width || 48), Number(config.height || sprite.height || 48)) / 2;
    for (let index = 1; index < state.board.steps.length; index += 1) {
      const start = pointOf(state.board.steps[index - 1]);
      const end = pointOf(state.board.steps[index]);
      if (distanceToSegment(point, start, end) <= radius + 18) return true;
    }
    return false;
  }

  function layerInspectorFields(entity) {
    return `
      <div class="editor-grid-2">
        <label class="editor-field">เลเยอร์
          <select data-edit="entity.layerSlot">
            ${["decor-back", "path", "reward", "decor-front", "fx-front"].map((slot) => `<option value="${slot}"${entity.layerSlot === slot ? " selected" : ""}>${layerSlotLabel(slot)}</option>`).join("")}
          </select>
        </label>
        <label class="editor-field">ลำดับ <input data-edit="entity.zIndex" type="number" step="1" value="${Number(entity.zIndex || 0)}"></label>
      </div>
      <div class="editor-actions" style="margin-bottom:8px">
        <button class="ui tiny button${entity.visible === false ? "" : " primary"}" data-action="toggle-visible" type="button"><i class="${entity.visible === false ? "fa-regular fa-eye-slash" : "fa-regular fa-eye"}"></i> ${entity.visible === false ? "ซ่อนอยู่" : "แสดงอยู่"}</button>
        <button class="ui tiny button${entity.locked ? " primary" : ""}" data-action="toggle-lock" type="button"><i class="${entity.locked ? "fa-solid fa-lock" : "fa-solid fa-lock-open"}"></i> ${entity.locked ? "ล็อค" : "ปลดล็อค"}</button>
      </div>
    `;
  }

  function renderSpriteStateFields(sprite) {
    const states = plainObject(sprite.states) ? sprite.states : {};
    const enabled = Array.isArray(sprite.enabledStates) ? sprite.enabledStates : ["idle"];
    const maxFrame = Math.max(0, Number(sprite.frameCount || 1) - 1);
    return `
      <section class="editor-warning-box" style="margin:8px 0">
        เปิดเฉพาะสถานะที่ต้องใช้จริง ถ้าไม่เปิด ระบบจะใช้ “วางเฉยๆ” ตลอด เหมาะกับ sprite ตกแต่งฉาก
      </section>
      <div class="editor-state-stack">
        ${["idle", "touch", "notReady", "ready", "claimed"].map((name) => {
          const stateConfig = states[name] || {};
          const label = { idle: "วางเฉยๆ", touch: "แตะ/กด", notReady: "ยังไม่พร้อม", ready: "พร้อมใช้งาน", claimed: "รับแล้ว" }[name];
          const isOn = name === "idle" || enabled.includes(name);
          return `
            <section class="editor-state-card${isOn ? " is-enabled" : ""}">
              <div class="editor-state-card-head">
                <strong>${label}</strong>
                ${name === "idle" ? '<span class="editor-muted">ค่าเริ่มต้น</span>' : `<button class="ui mini button${isOn ? " primary" : ""}" data-action="toggle-sprite-state" data-state-name="${name}" type="button">${isOn ? "เปิดอยู่" : "เปิดใช้"}</button>`}
              </div>
              ${isOn ? `
                <div class="editor-grid-2">
                  ${rangeField("เฟรม", `sprite.states.${name}.frameIndex`, Number(stateConfig.frameIndex || 0), 0, maxFrame, 1)}
                  ${rangeField("FPS", `sprite.states.${name}.fps`, Number(stateConfig.fps || sprite.fps || 12), 1, 60, 1)}
                  <label class="editor-field">โหมด
                    <select data-edit="sprite.states.${name}.mode">
                      ${["static", "loop", "once", "pingpong"].map((mode) => `<option value="${mode}"${(stateConfig.mode || "static") === mode ? " selected" : ""}>${mode}</option>`).join("")}
                    </select>
                  </label>
                </div>
              ` : ""}
            </section>
          `;
        }).join("")}
      </div>
    `;
  }

  function drawSelectedSpritePreview(sprite) {
    const canvas = document.getElementById("selectedSpritePreview");
    if (!canvas || !sprite) return;
    const ctx = canvas.getContext("2d");
    const dpr = Math.max(1, Math.min(window.devicePixelRatio || 1, 2));
    const cssWidth = canvas.getBoundingClientRect().width || 160;
    const cssHeight = 120;
    canvas.width = Math.round(cssWidth * dpr);
    canvas.height = Math.round(cssHeight * dpr);
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    ctx.clearRect(0, 0, cssWidth, cssHeight);
    const image = loadImage(sprite.src, () => drawSelectedSpritePreview(sprite));
    if (!image) {
      ctx.fillStyle = "rgba(238,243,251,.7)";
      ctx.font = "12px system-ui, sans-serif";
      ctx.textAlign = "center";
      ctx.fillText("กำลังโหลดพรีวิว", cssWidth / 2, cssHeight / 2);
      return;
    }
    const config = activeSpriteConfig(sprite);
    const frame = spriteFrameIndex(config, performance.now());
    const box = frameBox(config, image, frame);
    const scale = Math.min((cssWidth - 18) / box.width, (cssHeight - 18) / box.height);
    const width = box.width * scale;
    const height = box.height * scale;
    const renderSource = spriteRenderSource(config, image, frame, width, height);
    ctx.imageSmoothingEnabled = false;
    ctx.drawImage(
      renderSource.image,
      renderSource.crop.x,
      renderSource.crop.y,
      renderSource.crop.width,
      renderSource.crop.height,
      (cssWidth - width) / 2,
      (cssHeight - height) / 2,
      width,
      height
    );
  }

  function selectedEntity() {
    const selected = selectedSingle();
    if (!selected) return null;
    return collectionForType(selected.type)?.[selected.index] || null;
  }

  function coerceEditValue(path, rawValue) {
    if (
      path.endsWith(".amount") ||
      path.includes("width") ||
      path.includes("height") ||
      path.includes("columns") ||
      path.includes("rows") ||
      path.includes("frame") ||
      path.includes("fps") ||
      path.includes("edgeFade") ||
      path.includes("scale") ||
      path.includes("opacity") ||
      path.includes("anchor") ||
      path.includes("offset") ||
      path.includes("zIndex") ||
      path.includes("frameIndex") ||
      path.endsWith(".h") ||
      path.endsWith(".stepIndex") ||
      path === "board.width" ||
      path === "entity.xpx" ||
      path === "entity.ypx"
    ) {
      return Number(rawValue);
    }
    return rawValue;
  }

  function commitInspectorChange() {
    normalizeBoard();
    setDirty(true);
    pushHistory();
    drawBoard();
  }

  function replaceSegmentReferences(oldId, nextId) {
    if (!oldId || !nextId || oldId === nextId) return;
    [...state.board.steps, ...state.board.rewards, ...state.board.sprites, ...state.board.rewardNodes].forEach((entity) => {
      if (String(entity.segmentId || "") === oldId) entity.segmentId = nextId;
    });
  }

  function updateEditorResource(path, rawValue) {
    const value = coerceEditValue(path, rawValue);
    if (path === "board.width") {
      state.board.image.width = Math.max(1, Math.round(Number(value || 1)));
      commitInspectorChange();
      return true;
    }
    if (path.startsWith("segment.")) {
      const segment = selectedSegment();
      if (!segment) return false;
      const key = path.split(".")[1];
      if (key === "id") {
        const oldId = String(segment.id || "");
        const nextId = normalizeSegmentId(value);
        segment.id = nextId;
        state.selectedSegmentId = nextId;
        replaceSegmentReferences(oldId, nextId);
      } else if (key === "h") {
        segment.h = Math.max(1, Math.round(Number(value || 1)));
      } else {
        segment[key] = String(value || "");
      }
      commitInspectorChange();
      return true;
    }
    if (path.startsWith("iconTemplate.")) {
      const template = selectedIconTemplate();
      if (!template) return false;
      if (path.startsWith("iconTemplate.meta.")) {
        template.meta = plainObject(template.meta) ? template.meta : {};
        template.meta[path.replace("iconTemplate.meta.", "")] = value;
      } else {
        const key = path.split(".")[1];
        if (key === "id") {
          const oldId = String(template.id || "");
          const nextId = normalizeSegmentId(value).replace(/^segment_/, "icon_") || Editor.id("icon");
          template.id = nextId;
          state.selectedIconTemplateId = nextId;
          state.board.rewardNodes.forEach((node) => {
            if (String(node.iconTemplateId || "") === oldId) node.iconTemplateId = nextId;
          });
        } else {
          template[key] = value;
        }
      }
      commitInspectorChange();
      return true;
    }
    if (path.startsWith("rewardTemplate.")) {
      const template = selectedRewardTemplate();
      if (!template) return false;
      if (path.startsWith("rewardTemplate.meta.")) {
        template.meta = plainObject(template.meta) ? template.meta : {};
        template.meta[path.replace("rewardTemplate.meta.", "")] = value;
      } else {
        const key = path.split(".")[1];
        if (key === "id") {
          const oldId = String(template.id || "");
          const nextId = normalizeSegmentId(value).replace(/^segment_/, "reward_template_") || Editor.id("reward_template");
          template.id = nextId;
          state.selectedRewardTemplateId = nextId;
          state.board.rewardNodes.forEach((node) => {
            if (String(node.rewardTemplateId || "") === oldId) node.rewardTemplateId = nextId;
          });
        } else {
          template[key] = value;
        }
      }
      commitInspectorChange();
      return true;
    }
    return false;
  }

  function updateSelectedEntity(path, rawValue) {
    if (updateEditorResource(path, rawValue)) return;
    const selected = selectedSingle();
    const entity = selectedEntity();
    if (!selected || !entity) return;
    const value = coerceEditValue(path, rawValue);
    if (path === "entity.xpx" || path === "entity.ypx") {
      const p = pointOf(entity);
      setEntityPoint(entity, path === "entity.xpx" ? value : p.x, path === "entity.ypx" ? value : p.y);
    } else if (path === "entity.layerSlot") {
      entity.layerSlot = normalizeLayerSlot(value, entity.layerSlot || "decor-back");
    } else if (path === "entity.zIndex") {
      entity.zIndex = Number(value || 0);
    } else if (path.startsWith("rewardNode.meta.")) {
      entity.meta = plainObject(entity.meta) ? entity.meta : {};
      entity.meta[path.replace("rewardNode.meta.", "")] = value;
    } else if (path.startsWith("sprite.")) {
      if (path.startsWith("sprite.states.")) {
        const [, , stateName, key] = path.split(".");
        entity.states = plainObject(entity.states) ? entity.states : {};
        entity.states[stateName] = plainObject(entity.states[stateName]) ? entity.states[stateName] : {};
        entity.states[stateName][key] = value;
        if (key === "mode" && value === "static") {
          entity.states[stateName].frameCount = entity.states[stateName].frameCount || entity.frameCount || 1;
        }
        commitInspectorChange();
        return;
      }
      if (path.startsWith("sprite.meta.")) {
        entity.meta = plainObject(entity.meta) ? entity.meta : {};
        entity.meta[path.replace("sprite.meta.", "")] = value;
        commitInspectorChange();
        return;
      }
      const key = path.split(".")[1];
      if (key === "width" && state.spriteRatioLocked) {
        const ratio = Math.max(0.0001, Number(entity.height || 1) / Math.max(1, Number(entity.width || 1)));
        entity.width = Math.max(1, Math.round(value));
        entity.height = Math.max(1, Math.round(entity.width * ratio));
      } else if (key === "height" && state.spriteRatioLocked) {
        const ratio = Math.max(0.0001, Number(entity.width || 1) / Math.max(1, Number(entity.height || 1)));
        entity.height = Math.max(1, Math.round(value));
        entity.width = Math.max(1, Math.round(entity.height * ratio));
      } else {
        entity[key] = key === "stepIndex" && value < 0 ? -1 : value;
      }
    } else if (path.startsWith("rewardNode.")) {
      const key = path.split(".")[1];
      entity[key] = key === "stepIndex" && value < 0 ? null : value;
    } else if (path.startsWith("reward.")) {
      entity[path.split(".")[1]] = value;
    } else if (path.startsWith("step.")) {
      entity[path.split(".")[1]] = value;
    } else if (path.startsWith(`${selected.type}.`)) {
      entity[path.split(".")[1]] = value;
    }
    commitInspectorChange();
  }

  function deleteSelected() {
    if (!state.selected.length) return;
    const grouped = new Map();
    state.selected.forEach((key) => {
      const parsed = parseItemKey(key);
      if (!grouped.has(parsed.type)) grouped.set(parsed.type, []);
      grouped.get(parsed.type).push(parsed.index);
    });
    grouped.forEach((indexes, type) => {
      const collection = collectionForType(type);
      if (!collection) return;
      indexes.sort((a, b) => b - a).forEach((index) => collection.splice(index, 1));
    });
    normalizeBoard();
    clearSelection();
    setDirty(true);
    pushHistory();
    drawBoard();
  }

  function duplicateSelected() {
    const selected = selectedSingle();
    if (!selected) return;
    const collection = collectionForType(selected.type);
    const entity = collection?.[selected.index];
    if (!entity) return;
    const clone = Editor.deepClone(entity);
    if (selected.type === "sprite") clone.id = Editor.id("sprite");
    if (selected.type === "reward-node") clone.id = Editor.id("reward_node");
    if (selected.type === "reward") clone.id = Editor.id("reward");
    const p = pointOf(entity);
    setEntityPoint(clone, p.x + 24, p.y + 24);
    collection.splice(selected.index + 1, 0, clone);
    normalizeBoard();
    selectItems([{ type: selected.type, index: selected.index + 1 }]);
    setDirty(true);
    pushHistory();
    drawBoard();
  }

  function moveSelectedZ(direction) {
    if (!state.selected.length) return;
    state.selected.forEach((key) => {
      const { type, index } = parseItemKey(key);
      const entity = collectionForType(type)?.[index];
      if (!entity || entity.locked) return;
      entity.zIndex = Number(entity.zIndex || 0) + (direction > 0 ? 1 : -1);
    });
    setDirty(true);
    pushHistory();
    drawBoard();
  }

  function sendSelectedSpriteUnderPath() {
    const selected = selectedSingle();
    if (!selected || selected.type !== "sprite") return;
    const sprite = state.board.sprites[selected.index];
    if (!sprite) return;
    sprite.layerSlot = "decor-back";
    sprite.zIndex = Math.min(Number(sprite.zIndex || 0), 0);
    setDirty(true);
    pushHistory();
    drawBoard();
    Editor.toast("ส่ง Sprite ไปใต้ทางเดินแล้ว", "good");
  }

  function toggleSelectedSpriteState(name) {
    const selected = selectedSingle();
    if (!selected || selected.type !== "sprite" || name === "idle") return;
    if (!["touch", "notReady", "ready", "claimed"].includes(name)) return;
    const sprite = state.board.sprites[selected.index];
    if (!sprite) return;
    sprite.enabledStates = Array.isArray(sprite.enabledStates) ? sprite.enabledStates : ["idle"];
    if (sprite.enabledStates.includes(name)) {
      sprite.enabledStates = sprite.enabledStates.filter((item) => item !== name);
    } else {
      sprite.enabledStates.push(name);
      sprite.states = plainObject(sprite.states) ? sprite.states : {};
      sprite.states[name] = {
        ...(sprite.states.idle || {}),
        ...(sprite.states[name] || {}),
        mode: sprite.states[name]?.mode || "static"
      };
    }
    normalizeBoard();
    setDirty(true);
    pushHistory();
    drawBoard();
  }

  function previewSelectedSprite() {
    const selected = selectedSingle();
    if (!selected || selected.type !== "sprite") return;
    const sprite = state.board.sprites[selected.index];
    if (!sprite) return;
    state.previewStep = Number(sprite.stepIndex ?? sprite.meta?.stepIndex ?? state.previewStep ?? -1);
    syncPreview();
    drawSelectedSpritePreview(sprite);
    Editor.toast("พรีวิว Sprite ที่เลือกแล้ว", "good");
  }

  async function copySelectedPosition() {
    const selected = selectedSingle();
    const entity = selectedEntity();
    if (!selected || !entity) return;
    const p = pointOf(entity);
    await Editor.copyText(JSON.stringify({
      type: selected.type,
      index: selected.index,
      segmentId: entity.segmentId || "",
      localX: Number(entity.localX ?? 0),
      localY: Number(entity.localY ?? 0),
      x: Math.round(p.x),
      y: Math.round(p.y)
    }, null, 2));
  }

  async function handleSpriteUpload(event) {
    const file = event.target.files?.[0] || null;
    if (!file) return;
    const sprite = selectedEntity();
    if (!sprite) return;
    try {
      const data = await Editor.uploadAsset(apiUrl.toString(), file, "sprite", { boardCode });
      sprite.src = data.path;
      sprite.frameWidth = sprite.frameWidth || data.width;
      sprite.frameHeight = sprite.frameHeight || data.height;
      sprite.width = sprite.width || data.width;
      sprite.height = sprite.height || data.height;
      setDirty(true);
      pushHistory();
      drawBoard();
      Editor.toast("อัปโหลด Sprite แล้ว", "good");
    } catch (error) {
      Editor.toast(error.message || "อัปโหลดไม่สำเร็จ", "bad");
    } finally {
      event.target.value = "";
    }
  }

  async function handleIconUpload(event) {
    const file = event.target.files?.[0] || null;
    if (!file) return;
    try {
      const data = await Editor.uploadAsset(apiUrl.toString(), file, "reward_icon", { boardCode });
      const template = {
        id: Editor.id("icon"),
        label: file.name.replace(/\.[^.]+$/, ""),
        src: data.path,
        frameX: 0,
        frameY: 0,
        frameWidth: data.width,
        frameHeight: data.height,
        columns: 1,
        rows: 1,
        frameCount: 1,
        fps: 12,
        mode: "loop",
        scale: 1,
        anchorX: 0.5,
        anchorY: 0.5,
        offsetX: 0,
        offsetY: 0,
        meta: {}
      };
      state.board.iconTemplates.push(template);
      state.selectedIconTemplateId = template.id;
      setDirty(true);
      pushHistory();
      drawBoard();
      Editor.toast("อัปโหลดไอคอนแล้ว", "good");
    } catch (error) {
      Editor.toast(error.message || "อัปโหลดไม่สำเร็จ", "bad");
    } finally {
      event.target.value = "";
    }
  }

  async function handleSelectedIconUpload(event) {
    const file = event.target.files?.[0] || null;
    const template = selectedIconTemplate();
    if (!file || !template) return;
    try {
      const data = await Editor.uploadAsset(apiUrl.toString(), file, "reward_icon", { boardCode });
      template.src = data.path;
      template.frameX = 0;
      template.frameY = 0;
      template.frameWidth = data.width || template.frameWidth || 44;
      template.frameHeight = data.height || template.frameHeight || 44;
      template.columns = template.columns || 1;
      template.rows = template.rows || 1;
      template.frameCount = template.frameCount || 1;
      template.label = template.label || file.name.replace(/\.[^.]+$/, "");
      setDirty(true);
      pushHistory();
      drawBoard();
      Editor.toast("อัปเดตไอคอนแล้ว", "good");
    } catch (error) {
      Editor.toast(error.message || "อัปโหลดไม่สำเร็จ", "bad");
    } finally {
      event.target.value = "";
    }
  }

  async function handleSegmentUpload(event) {
    const file = event.target.files?.[0] || null;
    const segment = selectedSegment();
    if (!file || !segment) return;
    try {
      const data = await Editor.uploadAsset(apiUrl.toString(), file, "segment", { boardCode });
      segment.src = data.path;
      if (Number(data.height || 0) > 0) segment.h = Math.round(Number(data.height));
      if (boardWidth() <= 1 && Number(data.width || 0) > 0) {
        state.board.image.width = Math.round(Number(data.width));
      }
      setDirty(true);
      pushHistory();
      drawBoard();
      Editor.toast("อัปโหลดพื้นหลังแล้ว", "good");
    } catch (error) {
      Editor.toast(error.message || "อัปโหลดไม่สำเร็จ", "bad");
    } finally {
      event.target.value = "";
    }
  }

  function addIconTemplate() {
    const template = {
      id: Editor.id("icon"),
      label: "Icon template",
      src: "",
      frameX: 0,
      frameY: 0,
      frameWidth: 44,
      frameHeight: 44,
      columns: 1,
      rows: 1,
      frameCount: 1,
      fps: 12,
      mode: "loop",
      scale: 1,
      anchorX: 0.5,
      anchorY: 0.5,
      offsetX: 0,
      offsetY: 0,
      meta: {}
    };
    state.board.iconTemplates.push(template);
    state.selectedIconTemplateId = template.id;
    setDirty(true);
    pushHistory();
    drawBoard();
  }

  function addRewardTemplate() {
    const template = {
      id: Editor.id("reward_template"),
      label: "Reward template",
      rewardTemplateId: "",
      mode: "fixed",
      meta: {}
    };
    state.board.rewardTemplates.push(template);
    state.selectedRewardTemplateId = template.id;
    setDirty(true);
    pushHistory();
    drawBoard();
  }

  function addSegment(position) {
    const source = boardSegments()[0]?.src || "";
    const segment = {
      id: `segment_${String(boardSegments().length + 1).padStart(3, "0")}`,
      src: source,
      h: Math.max(800, Math.round(Number(boardSegments()[0]?.h || 800))),
      y: 0
    };
    if (position === "top") state.board.image.segments.unshift(segment);
    else state.board.image.segments.push(segment);
    state.selectedSegmentId = segment.id;
    normalizeBoard();
    setDirty(true);
    pushHistory();
    drawBoard();
  }

  function deleteSelectedSegment() {
    if (!state.selectedSegmentId || boardSegments().length <= 1) return;
    const used = [...state.board.steps, ...state.board.rewards, ...state.board.sprites, ...state.board.rewardNodes].some((entity) => entity.segmentId === state.selectedSegmentId);
    if (used) {
      Editor.toast("พื้นหลังนี้ยังมี object ผูกอยู่", "bad");
      return;
    }
    state.board.image.segments = boardSegments().filter((segment) => segment.id !== state.selectedSegmentId);
    state.selectedSegmentId = String(state.board.image.segments[0]?.id || "");
    normalizeBoard();
    setDirty(true);
    pushHistory();
    drawBoard();
  }

  function deleteSelectedIconTemplate() {
    const id = state.selectedIconTemplateId;
    if (!id) return;
    state.board.iconTemplates = state.board.iconTemplates.filter((template) => String(template.id || "") !== id);
    state.board.rewardNodes.forEach((node) => {
      if (String(node.iconTemplateId || "") === id) node.iconTemplateId = "";
    });
    state.selectedIconTemplateId = String(state.board.iconTemplates[0]?.id || "");
    setDirty(true);
    pushHistory();
    drawBoard();
  }

  function deleteSelectedRewardTemplate() {
    const id = state.selectedRewardTemplateId;
    if (!id) return;
    state.board.rewardTemplates = state.board.rewardTemplates.filter((template) => String(template.id || "") !== id);
    state.board.rewardNodes.forEach((node) => {
      if (String(node.rewardTemplateId || "") === id) node.rewardTemplateId = "";
    });
    state.selectedRewardTemplateId = String(state.board.rewardTemplates[0]?.id || "");
    setDirty(true);
    pushHistory();
    drawBoard();
  }

  async function previewRewardNode() {
    const selected = selectedSingle();
    if (!selected || selected.type !== "reward-node") return;
    const node = state.board.rewardNodes[selected.index];
    try {
      const data = await api("preview_reward", { reward: node });
      const message = data.resolved
        ? `ทดลองรางวัล ${node.rewardTemplateId || node.id}`
        : `ทดลองเท่านั้น: ${node.meta?.kind || "coin"} x${node.meta?.amount || 1}`;
      Editor.toast(message, "good");
      pulseNode(node);
    } catch (error) {
      Editor.toast(error.message || "พรีวิวไม่สำเร็จ", "bad");
    }
  }

  async function loadAssets() {
    if (!refs.assetBrowserList) return;
    try {
      const data = await api("list_assets");
      state.assets = Array.isArray(data.assets) ? data.assets : [];
      if (!state.selectedAssetPath && state.assets[0]?.path) {
        state.selectedAssetPath = String(state.assets[0].path || "");
      }
      renderAssetBrowser();
    } catch (error) {
      refs.assetBrowserList.innerHTML = `<div class="editor-note">โหลดคลังไฟล์ไม่สำเร็จ: ${Editor.escapeHtml(error.message || "")}</div>`;
      if (refs.assetPreview) {
        refs.assetPreview.innerHTML = `<div class="editor-note">โหลดตัวอย่างไฟล์ไม่ได้: ${Editor.escapeHtml(error.message || "")}</div>`;
      }
    }
  }

  function currentAssetPickTarget() {
    const explicit = String(refs.assetBrowserList?.dataset.pickTarget || "").trim();
    if (explicit) return explicit;
    if (state.selectedIconTemplateId && selectedIconTemplate()) return "icon";
    const selected = selectedSingle();
    if (selected?.type === "sprite") return "sprite";
    return "";
  }

  function assetApplyLabel(target) {
    return {
      segment: "ใช้กับพื้นหลังช่วงนี้",
      icon: "ใช้กับไอคอนรางวัล",
      sprite: "ใช้กับ Sprite ที่เลือก"
    }[String(target || "")] || "ใช้ไฟล์นี้";
  }

  function assetSearchQuery() {
    return String(state.assetSearch || "").trim().toLowerCase();
  }

  function filteredAssets() {
    const query = assetSearchQuery();
    return state.assets.filter((asset) => {
      if (!query) return true;
      const haystack = [
        asset.name || "",
        asset.path || "",
        `${Number(asset.width || 0)}x${Number(asset.height || 0)}`
      ].join(" ").toLowerCase();
      return haystack.includes(query);
    });
  }

  function selectedAssetRecord() {
    const assets = filteredAssets();
    if (!assets.length) return null;
    const active = assets.find((asset) => String(asset.path || "") === String(state.selectedAssetPath || ""));
    return active || assets[0] || null;
  }

  function assetRecordByPath(path) {
    const target = String(path || "");
    return state.assets.find((asset) => String(asset.path || "") === target) || null;
  }

  function spriteConfigFromAsset(path) {
    const asset = assetRecordByPath(path);
    const meta = plainObject(asset?.spriteMeta) ? asset.spriteMeta : {};
    return {
      src: String(path || meta.src || ""),
      columns: Math.max(1, Math.round(Number(meta.columns || 1))),
      rows: Math.max(1, Math.round(Number(meta.rows || 1))),
      frameWidth: Math.max(0, Math.round(Number(meta.frameWidth || asset?.width || 0))),
      frameHeight: Math.max(0, Math.round(Number(meta.frameHeight || asset?.height || 0))),
      frameCount: Math.max(1, Math.round(Number(meta.frameCount || 1))),
      fps: Editor.clamp(Number(meta.fps || 12), 1, 60),
      mode: ["static", "once", "loop", "pingpong"].includes(String(meta.mode || "")) ? meta.mode : "loop",
      edgeFade: defaultSpriteEdgeFade(path, meta.edgeFade),
      width: Math.max(1, Math.round(Number(meta.width || meta.frameWidth || asset?.width || 96))),
      height: Math.max(1, Math.round(Number(meta.height || meta.frameHeight || asset?.height || 96)))
    };
  }

  function applySpriteConfig(sprite, config = {}) {
    if (!sprite || !plainObject(config)) return;
    if (config.src) sprite.src = String(config.src || "");
    sprite.columns = Math.max(1, Math.round(Number(config.columns || sprite.columns || 1)));
    sprite.rows = Math.max(1, Math.round(Number(config.rows || sprite.rows || 1)));
    sprite.frameWidth = Math.max(0, Math.round(Number(config.frameWidth || sprite.frameWidth || 0)));
    sprite.frameHeight = Math.max(0, Math.round(Number(config.frameHeight || sprite.frameHeight || 0)));
    sprite.frameCount = Editor.clamp(Math.round(Number(config.frameCount || sprite.frameCount || 1)), 1, sprite.columns * sprite.rows);
    sprite.fps = Editor.clamp(Number(config.fps || sprite.fps || 12), 1, 60);
    sprite.mode = ["static", "once", "loop", "pingpong"].includes(String(config.mode || "")) ? config.mode : sprite.mode || "loop";
    sprite.width = Math.max(1, Math.round(Number(config.width || sprite.width || sprite.frameWidth || 96)));
    sprite.height = Math.max(1, Math.round(Number(config.height || sprite.height || sprite.frameHeight || 96)));
    sprite.meta = plainObject(sprite.meta) ? sprite.meta : {};
    sprite.meta.edgeFade = defaultSpriteEdgeFade(sprite.src, config.edgeFade ?? sprite.meta.edgeFade);
    sprite.states = plainObject(sprite.states) ? sprite.states : {};
    sprite.states.idle = { ...(sprite.states.idle || {}), frameIndex: Number(sprite.states.idle?.frameIndex || 0), mode: sprite.mode === "static" ? "static" : (sprite.states.idle?.mode || sprite.mode || "loop") };
  }

  function setSelectedAsset(path) {
    state.selectedAssetPath = String(path || "").trim();
    renderAssetBrowser();
  }

  function renderAssetPreview() {
    if (!refs.assetPreview) return;
    const asset = selectedAssetRecord();
    const pickTarget = currentAssetPickTarget();
    refs.assetPreview.classList.toggle("is-picking", Boolean(pickTarget));
    if (!asset) {
      refs.assetPreview.innerHTML = '<div class="editor-note">ยังไม่พบไฟล์ที่ตรงกับคำค้น</div>';
      return;
    }
    const path = Editor.escapeHtml(asset.path || "");
    const name = Editor.escapeHtml(asset.name || asset.path || "");
    refs.assetPreview.innerHTML = `
      <div class="editor-asset-preview-card">
        <div class="editor-asset-preview-media">
          <img src="${path}" alt="${name}" loading="lazy">
        </div>
        <div class="editor-asset-preview-copy">
          <strong>${name}</strong>
          <div class="editor-asset-meta">${Number(asset.width || 0)} x ${Number(asset.height || 0)}</div>
          <div class="editor-asset-path">${path}</div>
          <div class="editor-actions" style="margin-top:6px">
            ${pickTarget ? `<button class="ui tiny primary button" type="button" data-asset-apply="${path}"><i class="fa-solid fa-check"></i> ${Editor.escapeHtml(assetApplyLabel(pickTarget))}</button>` : ""}
            <button class="ui tiny button" type="button" data-asset-copy="${path}"><i class="fa-solid fa-link"></i> คัดลอก path</button>
            <a class="ui tiny button" href="${path}" target="_blank" rel="noopener"><i class="fa-solid fa-arrow-up-right-from-square"></i> เปิดไฟล์</a>
          </div>
        </div>
      </div>
    `;
  }

  function renderAssetBrowser() {
    if (!refs.assetBrowserList) return;
    const list = filteredAssets().slice(0, 120);
    if (!list.find((asset) => String(asset.path || "") === String(state.selectedAssetPath || ""))) {
      state.selectedAssetPath = String(list[0]?.path || "");
    }
    refs.assetBrowserList.classList.toggle("is-grid", state.assetView === "grid");
    refs.assetBrowserList.classList.toggle("is-list", state.assetView === "list");
    refs.assetBrowserList.innerHTML = list.map((asset) => {
      const path = Editor.escapeHtml(asset.path || "");
      const name = Editor.escapeHtml(asset.name || asset.path || "");
      return `
        <button class="editor-asset-card is-draggable${String(asset.path || "") === String(state.selectedAssetPath || "") ? " is-active" : ""}" type="button" draggable="true" data-asset-path="${path}">
          <span class="editor-asset-thumb"><img src="${path}" alt="${name}" loading="lazy"></span>
          <span class="editor-asset-copy">
            <strong class="editor-asset-title">${name}</strong>
            <span class="editor-asset-meta">${Number(asset.width || 0)} x ${Number(asset.height || 0)}${asset.spriteMeta ? " / sprite config" : ""}</span>
            <span class="editor-asset-path">${path}</span>
          </span>
        </button>
      `;
    }).join("") || '<div class="editor-note">ยังไม่พบรูปในโฟลเดอร์ที่อนุญาต</div>';
    renderAssetPreview();
  }

  function pickAssetFor(target) {
    if (!state.assets.length) {
      Editor.toast("กำลังโหลดคลังไฟล์", "warn");
      loadAssets();
      return;
    }
    Editor.toast("เลือกไฟล์จากคลัง แล้วกดใช้ไฟล์นี้ได้เลย", "good");
    refs.assetBrowserList?.classList.add("is-picking");
    refs.assetBrowserList.dataset.pickTarget = target;
    renderAssetPreview();
  }

  function applyAssetPath(target, path) {
    const selected = selectedSingle();
    if (target === "segment") {
      const segment = selectedSegment();
      if (segment) segment.src = path;
    } else if (target === "icon") {
      const template = selectedIconTemplate();
      if (template) template.src = path;
    } else if (target === "sprite") {
      const sprite = selected?.type === "sprite" ? selectedEntity() : null;
      if (sprite) applySpriteConfig(sprite, spriteConfigFromAsset(path));
    }
    setDirty(true);
    pushHistory();
    drawBoard();
    refs.assetBrowserList.dataset.pickTarget = "";
    refs.assetBrowserList?.classList.remove("is-picking");
    renderAssetPreview();
  }

  function setViewMode(mode) {
    state.viewMode = ["design", "preview", "split"].includes(mode) ? mode : "design";
    refs.viewButtons.forEach((button) => button.classList.toggle("is-active", button.dataset.viewMode === state.viewMode));
    refs.workspace?.classList.toggle("is-design-only", state.viewMode === "design");
    refs.workspace?.classList.toggle("is-preview-only", state.viewMode === "preview");
    refs.workspace?.classList.toggle("is-split", state.viewMode === "split");
    if (state.viewMode !== "design") {
      ensurePreviewFrame();
    }
    window.setTimeout(() => {
      resizeStage();
      resizePreview();
    }, 60);
  }

  function stagePointFromClient(clientX, clientY) {
    const rect = state.stage.container().getBoundingClientRect();
    const scale = state.stage.scaleX() || 1;
    return {
      x: (clientX - rect.left - state.stage.x()) / scale,
      y: (clientY - rect.top - state.stage.y()) / scale
    };
  }

  function applyDragPayloadAt(payload, point) {
    if (!payload) return;
    if (payload.kind === "step") addStepAt(point, nearestStepInsertIndex(point));
    if (payload.kind === "reward-node") addRewardNodeAt(point);
    if (payload.kind === "icon-template") {
      state.selectedIconTemplateId = payload.iconTemplateId || "";
      addRewardNodeAt(point);
    }
    if (payload.kind === "sprite") addSpriteAt(point);
    if (payload.kind === "asset") {
      const selected = selectedSingle();
      const config = spriteConfigFromAsset(payload.path);
      if (selected?.type === "sprite") {
        const sprite = selectedEntity();
        applySpriteConfig(sprite, config);
        setDirty(true);
        pushHistory();
        drawBoard();
      } else {
        addSpriteAt(point, config);
      }
    }
    setTool("select");
  }

  function pulseNode(node) {
    const p = pointOf(node);
    const circle = new Konva.Circle({
      x: p.x,
      y: p.y,
      radius: 20,
      stroke: "#f7c76b",
      strokeWidth: 4,
      opacity: 0.9,
      listening: false
    });
    state.layers.simulation.add(circle);
    circle.to({
      radius: 120,
      opacity: 0,
      duration: 0.75,
      easing: Konva.Easings.EaseOut,
      onFinish: () => circle.destroy()
    });
  }

  function nudgeSelected(dx, dy) {
    if (!state.selected.length) return;
    state.selected.forEach((key) => {
      const { type, index } = parseItemKey(key);
      const entity = collectionForType(type)?.[index];
      if (!entity) return;
      const p = pointOf(entity);
      setEntityPoint(entity, p.x + dx, p.y + dy);
    });
    normalizeBoard();
    setDirty(true);
    pushHistory();
    drawBoard();
  }

  function handleKeyDown(event) {
    if (isTypingTarget(event.target)) return;
    if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === "z") {
      event.preventDefault();
      if (event.shiftKey) applySnapshot(state.history.redo(state.board));
      else applySnapshot(state.history.undo(state.board));
      return;
    }
    if (event.key === "Delete" || event.key === "Backspace") {
      event.preventDefault();
      deleteSelected();
      return;
    }
    if (event.code === "Space") {
      state.spacePan = true;
      state.stage.draggable(true);
      state.stage.container().style.cursor = editorCursor();
      return;
    }
    const arrows = { ArrowLeft: [-1, 0], ArrowRight: [1, 0], ArrowUp: [0, -1], ArrowDown: [0, 1] };
    if (arrows[event.key]) {
      event.preventDefault();
      const amount = event.altKey ? 1 : event.shiftKey ? 25 : 5;
      nudgeSelected(arrows[event.key][0] * amount, arrows[event.key][1] * amount);
    }
  }

  function handleKeyUp(event) {
    if (event.code !== "Space") return;
    state.spacePan = false;
    state.stage.draggable(state.tool === "pan");
    state.stage.container().style.cursor = editorCursor();
  }

  function isTypingTarget(target) {
    if (!(target instanceof HTMLElement)) return false;
    return ["INPUT", "TEXTAREA", "SELECT"].includes(target.tagName) || target.isContentEditable;
  }

  async function saveDraft() {
    try {
      normalizeBoard();
      const data = await api("save_draft", { board: state.board });
      state.board = data.board;
      state.hasDraft = true;
      state.previewBoardDirty = true;
      setDirty(false);
      drawBoard();
      Editor.toast("บันทึกร่างแล้ว", "good");
    } catch (error) {
      Editor.toast(error.message || "บันทึกร่างไม่สำเร็จ", "bad");
    }
  }

  async function publishDraft() {
    if (Editor.hasDataUrl(state.board)) {
      Editor.toast("เผยแพร่ไม่ได้: ต้องแทน data:image ด้วยไฟล์อัปโหลดก่อน", "bad");
      return;
    }
    try {
      normalizeBoard();
      const data = await api("publish_draft", { board: state.board });
      state.board = data.board;
      state.liveBoard = Editor.deepClone(data.board);
      state.versions = data.versions || state.versions;
      state.hasDraft = true;
      state.previewBoardDirty = true;
      setDirty(false);
      drawBoard();
      Editor.toast("เผยแพร่เป็นหน้าจริงแล้ว", "good");
    } catch (error) {
      Editor.toast(error.message || "เผยแพร่ไม่สำเร็จ", "bad");
    }
  }

  async function rollbackVersion(versionId, publish) {
    try {
      const data = await api("rollback_version", { versionId, publish: Boolean(publish) });
      state.board = data.board;
      state.versions = data.versions || state.versions;
      state.hasDraft = true;
      state.history.reset(state.board);
      state.previewBoardDirty = true;
      setDirty(false);
      drawBoard();
      Editor.toast(publish ? "เผยแพร่ version แล้ว" : "คืน version เป็นร่างแล้ว", "good");
    } catch (error) {
      Editor.toast(error.message || "ย้อน version ไม่สำเร็จ", "bad");
    }
  }

  function refreshAnimation(ts) {
    if (!state.stage) {
      window.requestAnimationFrame(refreshAnimation);
      return;
    }
    if (state.previewPlaying && state.board?.steps?.length) {
      const interval = 720 / Math.max(0.25, Number(state.previewSpeed || 1));
      if (!state.lastPreviewStepAt || ts - state.lastPreviewStepAt >= interval) {
        state.lastPreviewStepAt = ts;
        const max = Math.max(0, state.board.steps.length - 1);
        state.previewStep = Math.floor(Number(state.previewStep ?? -1)) + 1;
        if (state.previewStep > max) state.previewStep = -1;
        syncPreview();
      }
    }
    state.stage.find(".sprite-image").forEach((node) => {
      const index = Number(node.getAttr("spriteIndex"));
      const sprite = state.board?.sprites?.[index];
      if (sprite) {
        const config = activeSpriteConfig(sprite);
        const sourceImage = loadImage(config.src);
        if (!sourceImage) return;
        const width = Math.max(1, Number(config.width || sprite.width || 48));
        const height = Math.max(1, Number(config.height || sprite.height || 48));
        const frame = spriteFrameIndex(config, ts);
        const renderSource = spriteRenderSource(config, sourceImage, frame, width, height);
        node.image(renderSource.image);
        node.crop(renderSource.crop);
      }
    });
    state.stage.find(".reward-node-image").forEach((node) => {
      const index = Number(node.getAttr("rewardNodeIndex"));
      const rewardNode = state.board?.rewardNodes?.[index];
      const template = iconTemplateById(rewardNode?.iconTemplateId);
      const image = node.image();
      if (template && image) node.crop(frameBox(template, image, spriteFrameIndex(template, ts)));
    });
    new Set([state.layers.decorBack, state.layers.decorFront, state.layers.reward].filter(Boolean)).forEach((layer) => layer.batchDraw());
    window.requestAnimationFrame(refreshAnimation);
  }

  function bindDomEvents() {
    Editor.installTooltips(document);
    refs.toolButtons.forEach((button) => button.addEventListener("click", () => setTool(button.dataset.tool || "select")));
    refs.viewButtons.forEach((button) => button.addEventListener("click", () => setViewMode(button.dataset.viewMode || "design")));
    refs.fitButton.addEventListener("click", fitView);
    refs.stickyToolButton?.addEventListener("click", () => {
      state.stickyTool = !state.stickyTool;
      refs.stickyToolButton.classList.toggle("primary", state.stickyTool);
      refs.stickyToolButton.setAttribute("aria-pressed", state.stickyTool ? "true" : "false");
    });
    refs.undoButton.addEventListener("click", () => applySnapshot(state.history.undo(state.board)));
    refs.redoButton.addEventListener("click", () => applySnapshot(state.history.redo(state.board)));
    refs.saveDraftButton.addEventListener("click", saveDraft);
    refs.publishButton.addEventListener("click", publishDraft);
    refs.refreshButton.addEventListener("click", bootEditor);
    rangeSyncInputs.forEach((range) => {
      range.addEventListener("input", () => {
        const input = document.getElementById(range.dataset.rangeTarget || "");
        if (!input) return;
        input.value = range.value;
        input.dispatchEvent(new Event("input", { bubbles: true }));
      });
      range.addEventListener("change", () => {
        const input = document.getElementById(range.dataset.rangeTarget || "");
        if (!input) return;
        input.value = range.value;
        input.dispatchEvent(new Event("change", { bubbles: true }));
      });
    });
    refs.addTopSegmentButton.addEventListener("click", () => addSegment("top"));
    refs.addBottomSegmentButton.addEventListener("click", () => addSegment("bottom"));
    refs.deleteSegmentButton.addEventListener("click", deleteSelectedSegment);
    refs.iconUploadInput.addEventListener("change", handleIconUpload);
    refs.addIconTemplateButton.addEventListener("click", addIconTemplate);
    refs.addRewardTemplateButton.addEventListener("click", addRewardTemplate);
    refs.refreshAssetButton?.addEventListener("click", loadAssets);
    refs.assetSearchInput?.addEventListener("input", () => {
      state.assetSearch = refs.assetSearchInput.value || "";
      renderAssetBrowser();
    });
    refs.assetViewButtons.forEach((button) => {
      button.addEventListener("click", () => {
        state.assetView = button.dataset.assetView === "list" ? "list" : "grid";
        refs.assetViewButtons.forEach((item) => item.classList.toggle("is-active", item === button));
        renderAssetBrowser();
      });
    });
    refs.previewPlayButton?.addEventListener("click", () => {
      state.previewPlaying = !state.previewPlaying;
      if (state.previewPlaying && Number(state.previewStep ?? -1) < 0) state.previewStep = 0;
      state.lastPreviewStepAt = 0;
      refs.previewPlayButton.innerHTML = state.previewPlaying ? '<i class="fa-solid fa-pause"></i>' : '<i class="fa-solid fa-play"></i>';
      syncPreview();
    });
    refs.previewStepRange?.addEventListener("input", () => {
      state.previewStep = Number(refs.previewStepRange.value || -1);
      syncPreview();
    });
    refs.previewSpeedRange?.addEventListener("input", () => {
      state.previewSpeed = Number(refs.previewSpeedRange.value || 1);
      syncPreview();
    });
    refs.previewFriendsButton?.addEventListener("click", () => {
      state.previewFriends = !state.previewFriends;
      refs.previewFriendsButton.classList.toggle("primary", state.previewFriends);
      syncPreview();
    });
    refs.previewZoomOutButton?.addEventListener("click", () => postPreviewCommand("zoom-out"));
    refs.previewZoomInButton?.addEventListener("click", () => postPreviewCommand("zoom-in"));
    refs.previewFocusButton?.addEventListener("click", () => postPreviewCommand("focus-self"));
    refs.previewFitButton?.addEventListener("click", () => postPreviewCommand("fit"));
    refs.boardTitleInput.addEventListener("input", () => {
      state.board.title = refs.boardTitleInput.value;
      setDirty(true);
      refreshUi();
    });
    for (const [input, path] of [
      [refs.rewardMarkerSizeInput, "rewardMarker.size"],
      [refs.pickupScaleInput, "currencyPickup.scale"],
      [refs.pickupCountInput, "currencyPickup.countMultiplier"],
      [refs.fxPathGlowInput, "fx.pathGlow"],
      [refs.fxCloudInput, "fx.clouds"],
      [refs.fxFriendCountInput, "fx.friendCount"]
    ]) {
      if (!input) continue;
      input.addEventListener("change", () => {
        const ui = state.board.meta.ui;
        if (path === "rewardMarker.size") ui.rewardMarker.size = Number(input.value || 44);
        if (path === "currencyPickup.scale") ui.currencyPickup.scale = Number(input.value || 1.3);
        if (path === "currencyPickup.countMultiplier") ui.currencyPickup.countMultiplier = Number(input.value || 1.45);
        if (path === "fx.pathGlow") state.board.meta.fx.pathGlow = Number(input.value || 1);
        if (path === "fx.clouds") state.board.meta.fx.clouds = Number(input.value || 1);
        if (path === "fx.friendCount") state.board.meta.fx.friendCount = Number(input.value || 3);
        normalizeBoard();
        setDirty(true);
        pushHistory();
        drawBoard();
      });
      input.addEventListener("input", Editor.throttleFrame(() => {
        const ui = state.board.meta.ui;
        if (path === "rewardMarker.size") ui.rewardMarker.size = Number(input.value || 44);
        if (path === "currencyPickup.scale") ui.currencyPickup.scale = Number(input.value || 1.3);
        if (path === "currencyPickup.countMultiplier") ui.currencyPickup.countMultiplier = Number(input.value || 1.45);
        if (path === "fx.pathGlow") state.board.meta.fx.pathGlow = Number(input.value || 1);
        if (path === "fx.clouds") state.board.meta.fx.clouds = Number(input.value || 1);
        if (path === "fx.friendCount") state.board.meta.fx.friendCount = Number(input.value || 3);
        normalizeBoard();
        setDirty(true);
        drawBoard();
      }));
    }
    refs.segmentList.addEventListener("click", (event) => {
      const row = event.target.closest("[data-segment-id]");
      if (!row) return;
      state.selectedSegmentId = row.dataset.segmentId || "";
      state.selectedIconTemplateId = "";
      state.selectedRewardTemplateId = "";
      clearSelection();
      refreshUi();
    });
    refs.iconTemplateList.addEventListener("click", (event) => {
      const row = event.target.closest("[data-icon-template-id]");
      if (!row) return;
      state.selectedIconTemplateId = row.dataset.iconTemplateId || "";
      state.selectedRewardTemplateId = "";
      clearSelection();
      refreshUi();
    });
    refs.iconTemplateList.addEventListener("dragstart", (event) => {
      const row = event.target.closest("[data-icon-template-id]");
      if (!row) return;
      const payload = { kind: "icon-template", iconTemplateId: row.dataset.iconTemplateId || "" };
      event.dataTransfer.setData("application/json", JSON.stringify(payload));
      event.dataTransfer.effectAllowed = "copy";
    });
    refs.rewardTemplateList.addEventListener("click", (event) => {
      const row = event.target.closest("[data-reward-template-id]");
      if (!row) return;
      state.selectedRewardTemplateId = row.dataset.rewardTemplateId || "";
      state.selectedIconTemplateId = "";
      clearSelection();
      refreshUi();
    });
    refs.versionList.addEventListener("click", (event) => {
      const draft = event.target.closest("[data-version-draft]");
      const publish = event.target.closest("[data-version-publish]");
      if (draft) rollbackVersion(draft.dataset.versionDraft || "", false);
      if (publish) rollbackVersion(publish.dataset.versionPublish || "", true);
    });
    refs.layerList?.addEventListener("pointerdown", (event) => {
      const handle = event.target.closest(".editor-layer-handle");
      const row = handle?.closest("[data-layer-key]");
      if (!handle || !row || event.button !== 0) return;
      event.preventDefault();
      event.stopPropagation();
      state.layerPointerDrag = {
        dragKey: row.dataset.layerKey || "",
        targetKey: "",
        placeAfter: false
      };
      row.classList.add("is-dragging");
      document.addEventListener("pointermove", updateLayerPointerDrag, true);
      document.addEventListener("pointerup", finishLayerPointerDrag, true);
    });
    refs.layerList?.addEventListener("click", (event) => {
      if (state.layerPointerDrag) return;
      const row = event.target.closest("[data-layer-type]");
      if (!row) return;
      const type = row.dataset.layerType || "";
      const index = Number(row.dataset.layerIndex);
      const action = event.target.closest("[data-layer-action]")?.dataset.layerAction || "select";
      if (action === "visible") toggleEntityVisible(type, index);
      else if (action === "lock") toggleEntityLock(type, index);
      else selectItems([{ type, index }]);
    });
    refs.layerList?.addEventListener("dragstart", (event) => {
      const row = event.target.closest("[data-layer-key]");
      if (!row) return;
      event.dataTransfer.effectAllowed = "move";
      event.dataTransfer.setData("text/plain", row.dataset.layerKey || "");
      row.classList.add("is-dragging");
    });
    refs.layerList?.addEventListener("dragover", (event) => {
      const row = event.target.closest("[data-layer-key]");
      if (!row) return;
      event.preventDefault();
      const rect = row.getBoundingClientRect();
      const after = event.clientY > rect.top + rect.height / 2;
      refs.layerList.querySelectorAll(".editor-layer-row").forEach((item) => item.classList.remove("is-drop-before", "is-drop-after"));
      row.classList.add(after ? "is-drop-after" : "is-drop-before");
      const panelBody = refs.layerList.closest(".editor-panel-body");
      if (panelBody) {
        const bodyRect = panelBody.getBoundingClientRect();
        if (event.clientY < bodyRect.top + 46) panelBody.scrollTop -= 16;
        if (event.clientY > bodyRect.bottom - 46) panelBody.scrollTop += 16;
      }
    });
    refs.layerList?.addEventListener("dragleave", (event) => {
      if (refs.layerList.contains(event.relatedTarget)) return;
      refs.layerList.querySelectorAll(".editor-layer-row").forEach((item) => item.classList.remove("is-drop-before", "is-drop-after"));
    });
    refs.layerList?.addEventListener("drop", (event) => {
      const row = event.target.closest("[data-layer-key]");
      if (!row) return;
      event.preventDefault();
      const rect = row.getBoundingClientRect();
      const after = event.clientY > rect.top + rect.height / 2;
      reorderLayerRows(event.dataTransfer.getData("text/plain"), row.dataset.layerKey || "", after);
    });
    refs.layerList?.addEventListener("dragend", () => {
      refs.layerList.querySelectorAll(".editor-layer-row").forEach((item) => item.classList.remove("is-dragging", "is-drop-before", "is-drop-after"));
    });
    refs.assetBrowserList?.addEventListener("click", (event) => {
      const row = event.target.closest("[data-asset-path]");
      if (!row) return;
      const path = row.dataset.assetPath || "";
      const target = refs.assetBrowserList.dataset.pickTarget || "";
      if (target) {
        applyAssetPath(target, path);
      } else {
        setSelectedAsset(path);
      }
    });
    refs.assetBrowserList?.addEventListener("dblclick", (event) => {
      const row = event.target.closest("[data-asset-path]");
      if (!row) return;
      const path = row.dataset.assetPath || "";
      const target = currentAssetPickTarget();
      if (target) applyAssetPath(target, path);
      else setSelectedAsset(path);
    });
    refs.assetBrowserList?.addEventListener("dragstart", (event) => {
      const row = event.target.closest("[data-asset-path]");
      if (!row) return;
      state.selectedAssetPath = row.dataset.assetPath || "";
      renderAssetPreview();
      const payload = { kind: "asset", path: row.dataset.assetPath || "" };
      event.dataTransfer.setData("application/json", JSON.stringify(payload));
      event.dataTransfer.effectAllowed = "copy";
    });
    refs.assetPreview?.addEventListener("click", async (event) => {
      const applyButton = event.target.closest("[data-asset-apply]");
      if (applyButton) {
        const target = currentAssetPickTarget();
        if (target) applyAssetPath(target, applyButton.dataset.assetApply || state.selectedAssetPath || "");
        return;
      }
      const copyButton = event.target.closest("[data-asset-copy]");
      if (copyButton) {
        await Editor.copyText(copyButton.dataset.assetCopy || state.selectedAssetPath || "");
      }
    });
    refs.quickPalette?.addEventListener("dragstart", (event) => {
      const token = event.target.closest("[data-drag-kind]");
      if (!token) return;
      const payload = { kind: token.dataset.dragKind || "" };
      event.dataTransfer.setData("application/json", JSON.stringify(payload));
      event.dataTransfer.effectAllowed = "copy";
    });
    refs.stageHost.addEventListener("dragover", (event) => {
      event.preventDefault();
      event.dataTransfer.dropEffect = "copy";
    });
    refs.stageHost.addEventListener("drop", (event) => {
      event.preventDefault();
      let payload = null;
      try {
        payload = JSON.parse(event.dataTransfer.getData("application/json") || "{}");
      } catch (error) {
        payload = null;
      }
      applyDragPayloadAt(payload, stagePointFromClient(event.clientX, event.clientY));
    });
    refs.selectionInspector.addEventListener("change", (event) => {
      const input = event.target.closest("[data-edit]");
      if (!input) return;
      if (input.type === "number" && String(input.value).trim() === "") return;
      updateSelectedEntity(input.dataset.edit, input.value);
    });
    refs.selectionInspector.addEventListener("input", Editor.throttleFrame((event) => {
      const input = event.target.closest("[data-edit]");
      if (!input || input.tagName === "SELECT" || input.type !== "range") return;
      const numberPeer = input.closest(".editor-range-field")?.querySelector('input[type="number"][data-edit]');
      if (numberPeer) numberPeer.value = input.value;
      updateSelectedEntity(input.dataset.edit, input.value);
    }));
    refs.selectionInspector.addEventListener("focusin", () => {
      state.inspectorEditing = true;
    });
    refs.selectionInspector.addEventListener("pointerdown", (event) => {
      if (event.target.closest("[data-edit]")) state.inspectorEditing = true;
    });
    refs.selectionInspector.addEventListener("focusout", () => {
      window.setTimeout(() => {
        state.inspectorEditing = false;
        refreshSelectionInspector();
      }, 80);
    });
    refs.selectionInspector.addEventListener("keydown", (event) => {
      if (event.key !== "Enter") return;
      const input = event.target.closest("[data-edit]");
      if (!input || input.tagName === "TEXTAREA") return;
      event.preventDefault();
      if (!(input.type === "number" && String(input.value).trim() === "")) {
        updateSelectedEntity(input.dataset.edit, input.value);
      }
      input.blur();
    });
    refs.selectionInspector.addEventListener("click", (event) => {
      const button = event.target.closest("[data-action]");
      if (!button) return;
      const action = button.dataset.action;
      if (action === "delete-selected") deleteSelected();
      if (action === "delete-segment") deleteSelectedSegment();
      if (action === "delete-icon-template") deleteSelectedIconTemplate();
      if (action === "delete-reward-template") deleteSelectedRewardTemplate();
      if (action === "duplicate-step" || action === "duplicate-sprite" || action === "duplicate-reward-node") duplicateSelected();
      if (action === "insert-step-before" || action === "insert-step-after") {
        const selected = selectedSingle();
        const entity = selectedEntity();
        if (selected?.type === "step" && entity) {
          const p = pointOf(entity);
          addStepAt({ x: p.x + (action === "insert-step-before" ? -18 : 18), y: p.y }, selected.index + (action === "insert-step-before" ? 0 : 1));
        }
      }
      if (action === "toggle-visible") {
        const selected = selectedSingle();
        if (selected) toggleEntityVisible(selected.type, selected.index);
      }
      if (action === "toggle-lock") {
        const selected = selectedSingle();
        if (selected) toggleEntityLock(selected.type, selected.index);
      }
      if (action === "toggle-sprite-ratio") {
        state.spriteRatioLocked = !state.spriteRatioLocked;
        refreshSelectionInspector();
      }
      if (action === "toggle-sprite-state") {
        toggleSelectedSpriteState(button.dataset.stateName || "");
      }
      if (action === "send-sprite-under-path") sendSelectedSpriteUnderPath();
      if (action === "preview-reward-node") previewRewardNode();
      if (action.startsWith("pick-asset-")) pickAssetFor(action.replace("pick-asset-", ""));
    });
  }

  async function bootEditor() {
    try {
      const data = await api("editor_bootstrap");
      state.saveAllowed = Boolean(data.saveAllowed);
      state.liveBoard = data.liveBoard || data.board || emptyBoard();
      state.board = data.workingBoard || data.draftBoard || data.liveBoard || emptyBoard();
      state.versions = data.versions || { versions: [] };
      state.hasDraft = Boolean(data.hasDraft);
      normalizeBoard();
      state.previewBoardDirty = true;
      if (!state.stage) initStage();
      state.history.reset(state.board);
      setDirty(false);
      drawBoard();
      fitView();
      loadAssets();
      initPreviewRuntime();
      syncPreview();
      Editor.toast(state.hasDraft ? "โหลดฉบับร่างแล้ว" : "โหลดข้อมูลจริงแล้ว", "good");
    } catch (error) {
      console.error(error);
      state.board = emptyBoard();
      if (!state.stage) initStage();
      drawBoard();
      Editor.toast(error.message || "โหลด editor ไม่สำเร็จ", "bad");
    }
  }

  bindDomEvents();
  bootEditor();
  window.requestAnimationFrame(refreshAnimation);
})();
