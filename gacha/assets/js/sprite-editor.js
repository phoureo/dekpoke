(function () {
  "use strict";

  const Editor = window.DekpokeEditor;
  const boot = window.SPRITE_EDITOR_BOOT || {};
  const csrfToken = String(boot.csrfToken || "");
  const canExport = Boolean(boot.canExport);

  const refs = {
    root: document.getElementById("spriteEditorRoot"),
    stageHost: document.getElementById("spriteStage"),
    emptyState: document.querySelector(".sprite-stage-empty"),
    toolButtons: Array.from(document.querySelectorAll("[data-sprite-tool]")),
    activeToolLabel: document.getElementById("activeSpriteToolLabel"),
    statusPill: document.getElementById("spriteStatusPill"),
    fileInput: document.getElementById("spriteFileInput"),
    dropZone: document.getElementById("spriteDropZone"),
    assetButton: document.getElementById("spriteAssetButton"),
    assetSearchInput: document.getElementById("spriteAssetSearchInput"),
    assetViewButtons: Array.from(document.querySelectorAll("[data-sprite-asset-view]")),
    assetPreview: document.getElementById("spriteAssetPreview"),
    assetList: document.getElementById("spriteAssetList"),
    fitButton: document.getElementById("spriteFitButton"),
    resetCropButton: document.getElementById("spriteResetCropButton"),
    autoTrimButton: document.getElementById("spriteAutoTrimButton"),
    autoGridButton: document.getElementById("spriteAutoGridButton"),
    playButton: document.getElementById("spritePlayButton"),
    exportButton: document.getElementById("spriteExportButton"),
    copyPathButton: document.getElementById("copySpritePathButton"),
    copyConfigButton: document.getElementById("copyMileageConfigButton"),
    copySingleFrameConfigButton: document.getElementById("copySingleFrameConfigButton"),
    sendConfigButton: document.getElementById("sendMileageConfigButton"),
    sendSingleFrameConfigButton: document.getElementById("sendSingleFrameConfigButton"),
    columnsInput: document.getElementById("spriteColumnsInput"),
    rowsInput: document.getElementById("spriteRowsInput"),
    frameCountInput: document.getElementById("spriteFrameCountInput"),
    outputColumnsInput: document.getElementById("spriteOutputColumnsInput"),
    fpsInput: document.getElementById("spriteFpsInput"),
    modeInput: document.getElementById("spriteModeInput"),
    displayWidthInput: document.getElementById("spriteDisplayWidthInput"),
    displayHeightInput: document.getElementById("spriteDisplayHeightInput"),
    alphaThresholdInput: document.getElementById("spriteAlphaThresholdInput"),
    paddingInput: document.getElementById("spritePaddingInput"),
    selectedFrameInput: document.getElementById("spriteSelectedFrameInput"),
    cropXInput: document.getElementById("spriteCropXInput"),
    cropYInput: document.getElementById("spriteCropYInput"),
    cropWidthInput: document.getElementById("spriteCropWidthInput"),
    cropHeightInput: document.getElementById("spriteCropHeightInput"),
    cropAnchorButtons: Array.from(document.querySelectorAll("[data-crop-anchor]")),
    previewCanvas: document.getElementById("animationPreviewCanvas"),
    resultPreviewCanvas: document.getElementById("spriteResultAnimationCanvas"),
    previewBgInput: document.getElementById("spritePreviewBgInput"),
    resultPreview: document.getElementById("spriteResultPreview"),
    pathOutput: document.getElementById("spritePathOutput"),
    configOutput: document.getElementById("spriteConfigOutput"),
    zoomLabel: document.getElementById("spriteZoomLabel"),
    frameSizeLabel: document.getElementById("spriteFrameSizeLabel"),
    cropSizeLabel: document.getElementById("spriteCropSizeLabel"),
    resultLabel: document.getElementById("spriteResultLabel")
  };

  const state = {
    tool: "select",
    file: null,
    image: null,
    imageUrl: "",
    assets: [],
    assetsLoaded: false,
    sourceCanvas: null,
    sourceContext: null,
    stage: null,
    layers: {},
    imageNode: null,
    cropRect: null,
    transformer: null,
    selectedFrame: 0,
    cropMode: "manual",
    crop: { x: 0, y: 0, width: 1, height: 1 },
    result: null,
    resultConfig: null,
    resultImage: null,
    resultImageUrl: "",
    playing: true,
    lastPreviewAt: 0,
    previewFrame: 0,
    panning: false,
    spacePan: false,
    centerScaleModifier: false,
    ratioLockModifier: false,
    transformSession: null,
    middlePan: null,
    cropAnchor: "center",
    assetSearch: "",
    assetView: "grid",
    selectedAssetPath: ""
  };

  function intValue(input, fallback, min = 0, max = 999999) {
    const value = Math.round(Number(input?.value ?? fallback));
    return Math.max(min, Math.min(max, Number.isFinite(value) ? value : fallback));
  }

  function numberValue(input, fallback, min = 0, max = 999999) {
    const value = Number(input?.value ?? fallback);
    return Math.max(min, Math.min(max, Number.isFinite(value) ? value : fallback));
  }

  function frameWidth() {
    if (!state.image) return 1;
    return Math.max(1, Math.floor(state.image.naturalWidth / columns()));
  }

  function frameHeight() {
    if (!state.image) return 1;
    return Math.max(1, Math.floor(state.image.naturalHeight / rows()));
  }

  function columns() {
    return intValue(refs.columnsInput, 1, 1, 512);
  }

  function rows() {
    return intValue(refs.rowsInput, 1, 1, 512);
  }

  function frameCount() {
    return intValue(refs.frameCountInput, columns() * rows(), 1, Math.max(1, columns() * rows()));
  }

  function outputColumns() {
    return intValue(refs.outputColumnsInput, frameCount(), 1, frameCount());
  }

  function fps() {
    return numberValue(refs.fpsInput, 12, 1, 60);
  }

  function mode() {
    return ["loop", "once", "pingpong"].includes(refs.modeInput.value) ? refs.modeInput.value : "loop";
  }

  function displayWidth() {
    return intValue(refs.displayWidthInput, state.crop.width, 1, 4096);
  }

  function displayHeight() {
    return intValue(refs.displayHeightInput, state.crop.height, 1, 4096);
  }

  function frameRect(index = state.selectedFrame) {
    const fw = frameWidth();
    const fh = frameHeight();
    const col = index % columns();
    const row = Math.floor(index / columns());
    return { x: col * fw, y: row * fh, width: fw, height: fh };
  }

  function normalizeCrop(crop = state.crop) {
    const fw = frameWidth();
    const fh = frameHeight();
    const x = Editor.clamp(Math.round(Number(crop.x || 0)), 0, Math.max(0, fw - 1));
    const y = Editor.clamp(Math.round(Number(crop.y || 0)), 0, Math.max(0, fh - 1));
    const width = Editor.clamp(Math.round(Number(crop.width || fw)), 1, Math.max(1, fw - x));
    const height = Editor.clamp(Math.round(Number(crop.height || fh)), 1, Math.max(1, fh - y));
    return { x, y, width, height };
  }

  function setStatus(text, tone = "") {
    refs.statusPill.textContent = text;
    refs.statusPill.classList.toggle("is-good", tone === "good");
    refs.statusPill.classList.toggle("is-warn", tone === "warn");
  }

  function setTool(tool) {
    state.tool = tool;
    refs.activeToolLabel.textContent = tool === "pan" ? "เลื่อน" : "เลือก";
    refs.toolButtons.forEach((button) => button.classList.toggle("is-active", button.dataset.spriteTool === tool));
    if (state.stage) {
      state.stage.draggable(tool === "pan");
      refs.stageHost.style.cursor = spriteCursor();
    }
  }

  function hasCenterScaleModifier(event = null) {
    const source = event || {};
    return Boolean(
      state.centerScaleModifier
      || source.ctrlKey
      || source.metaKey
      || source.altKey
    );
  }

  function hasRatioLockModifier(event = null) {
    const source = event || {};
    return Boolean(state.ratioLockModifier || source.shiftKey);
  }

  function syncTransformerModifiers(
    centered = state.transformSession?.centered || state.centerScaleModifier,
    keepRatio = state.transformSession?.keepRatio || state.ratioLockModifier
  ) {
    if (!state.transformer) return;
    void centered;
    state.transformer.centeredScaling(false);
    state.transformer.keepRatio(Boolean(keepRatio));
    state.transformer.shiftBehavior("none");
    state.layers.crop?.batchDraw();
  }

  function setCenterScaleModifier(enabled) {
    const next = Boolean(enabled);
    if (state.centerScaleModifier === next) return;
    state.centerScaleModifier = next;
    if (state.transformSession?.type === "transform") {
      if (next && !state.transformSession.centered && state.cropRect) {
        state.transformSession.centered = true;
        state.transformSession.center = {
          x: state.cropRect.x() + (state.cropRect.width() / 2),
          y: state.cropRect.y() + (state.cropRect.height() / 2)
        };
      } else if (!next) {
        state.transformSession.centered = false;
      }
    }
    syncTransformerModifiers();
  }

  function setRatioLockModifier(enabled) {
    const next = Boolean(enabled);
    if (state.ratioLockModifier === next) return;
    state.ratioLockModifier = next;
    if (state.transformSession?.type === "transform") {
      state.transformSession.keepRatio = next;
    }
    syncTransformerModifiers();
  }

  function normalizedBox(box) {
    const rawWidth = Number(box?.width || 0);
    const rawHeight = Number(box?.height || 0);
    const width = Math.max(1, Math.abs(rawWidth));
    const height = Math.max(1, Math.abs(rawHeight));
    const x = rawWidth >= 0 ? Number(box?.x || 0) : Number(box?.x || 0) + rawWidth;
    const y = rawHeight >= 0 ? Number(box?.y || 0) : Number(box?.y || 0) + rawHeight;
    return { x, y, width, height, rotation: 0 };
  }

  function cropAnchorFactors(anchor = state.cropAnchor) {
    switch (anchor) {
      case "top-left":
        return { x: 0, y: 0 };
      case "top-center":
        return { x: 0.5, y: 0 };
      case "top-right":
        return { x: 1, y: 0 };
      case "middle-left":
        return { x: 0, y: 0.5 };
      case "middle-right":
        return { x: 1, y: 0.5 };
      case "bottom-left":
        return { x: 0, y: 1 };
      case "bottom-center":
        return { x: 0.5, y: 1 };
      case "bottom-right":
        return { x: 1, y: 1 };
      case "center":
      default:
        return { x: 0.5, y: 0.5 };
    }
  }

  function setCropAnchor(anchor) {
    const allowed = new Set([
      "top-left",
      "top-center",
      "top-right",
      "middle-left",
      "center",
      "middle-right",
      "bottom-left",
      "bottom-center",
      "bottom-right"
    ]);
    state.cropAnchor = allowed.has(anchor) ? anchor : "center";
    refs.cropAnchorButtons.forEach((button) => {
      button.classList.toggle("is-active", button.dataset.cropAnchor === state.cropAnchor);
    });
  }

  function cropAnchorPoint(crop = state.crop, anchor = state.cropAnchor) {
    const factors = cropAnchorFactors(anchor);
    return {
      x: Number(crop.x || 0) + (Number(crop.width || 1) * factors.x),
      y: Number(crop.y || 0) + (Number(crop.height || 1) * factors.y)
    };
  }

  function anchoredSizeLimit(anchorPointValue, anchorFactor, fullSize) {
    let limit = Number(fullSize || 1);
    if (anchorFactor > 0) {
      limit = Math.min(limit, anchorPointValue / anchorFactor);
    }
    if (anchorFactor < 1) {
      limit = Math.min(limit, (fullSize - anchorPointValue) / (1 - anchorFactor));
    }
    return Math.max(1, Math.floor(limit));
  }

  function cropFromAnchorPoint(anchorPoint, width, height, anchor = state.cropAnchor) {
    const factors = cropAnchorFactors(anchor);
    return normalizeCrop({
      x: anchorPoint.x - (width * factors.x),
      y: anchorPoint.y - (height * factors.y),
      width,
      height
    });
  }

  function anchoredCropFromDimensions(baseCrop, nextWidth, nextHeight, anchor = state.cropAnchor) {
    const point = cropAnchorPoint(baseCrop, anchor);
    const factors = cropAnchorFactors(anchor);
    const maxWidth = anchoredSizeLimit(point.x, factors.x, frameWidth());
    const maxHeight = anchoredSizeLimit(point.y, factors.y, frameHeight());
    const width = Editor.clamp(Math.round(Number(nextWidth || baseCrop.width || 1)), 1, maxWidth);
    const height = Editor.clamp(Math.round(Number(nextHeight || baseCrop.height || 1)), 1, maxHeight);
    return cropFromAnchorPoint(point, width, height, anchor);
  }

  function initStage() {
    const rect = refs.stageHost.getBoundingClientRect();
    state.stage = new Konva.Stage({
      container: refs.stageHost,
      width: Math.max(1, rect.width),
      height: Math.max(1, rect.height)
    });
    state.layers.image = new Konva.Layer();
    state.layers.grid = new Konva.Layer({ listening: false });
    state.layers.crop = new Konva.Layer();
    Object.values(state.layers).forEach((layer) => state.stage.add(layer));

    state.cropRect = new Konva.Rect({
      x: 0,
      y: 0,
      width: 1,
      height: 1,
      fill: "rgba(142, 161, 255, 0.12)",
      stroke: "#f7c76b",
      strokeWidth: 2,
      dash: [8, 5],
      draggable: true,
      name: "crop-box"
    });
    state.transformer = new Konva.Transformer({
      nodes: [state.cropRect],
      rotateEnabled: false,
      keepRatio: false,
      centeredScaling: false,
      shiftBehavior: "none",
      flipEnabled: false,
      borderStroke: "#f7c76b",
      borderDash: [8, 5],
      anchorFill: "#fff7d6",
      anchorStroke: "#f7c76b",
      anchorSize: 9,
      enabledAnchors: ["top-left", "top-center", "top-right", "middle-right", "bottom-right", "bottom-center", "bottom-left", "middle-left"],
      boundBoxFunc: (_, newBox) => normalizedBox(newBox)
    });
    state.layers.crop.add(state.cropRect);
    state.layers.crop.add(state.transformer);

    state.cropRect.on("dragmove", constrainCropRect);
    state.cropRect.on("dragend", commitCropRect);
    state.cropRect.on("transformstart", handleTransformStart);
    state.cropRect.on("transform", previewCropTransform);
    state.cropRect.on("transformend", handleTransformEnd);
    state.stage.on("wheel", handleWheel);
    state.stage.on("mousedown touchstart", handleStageDown);
    state.stage.on("mouseup touchend", handleStageUp);
    state.stage.on("contextmenu", handleContextMenu);
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
    fitView(false);
  }

  function stagePointer() {
    const pointer = state.stage.getPointerPosition();
    if (!pointer) return null;
    const scale = state.stage.scaleX() || 1;
    return {
      x: (pointer.x - state.stage.x()) / scale,
      y: (pointer.y - state.stage.y()) / scale
    };
  }

  function handleWheel(event) {
    if (!state.stage) return;
    event.evt.preventDefault();
    const oldScale = state.stage.scaleX() || 1;
    const pointer = state.stage.getPointerPosition();
    if (!pointer) return;
    const mousePointTo = {
      x: (pointer.x - state.stage.x()) / oldScale,
      y: (pointer.y - state.stage.y()) / oldScale
    };
    const direction = event.evt.deltaY > 0 ? -1 : 1;
    const nextScale = Editor.clamp(direction > 0 ? oldScale * 1.08 : oldScale / 1.08, 0.05, 6);
    state.stage.scale({ x: nextScale, y: nextScale });
    state.stage.position({
      x: pointer.x - mousePointTo.x * nextScale,
      y: pointer.y - mousePointTo.y * nextScale
    });
    state.stage.batchDraw();
    refreshStatus();
  }

  function spriteCursor() {
    if (state.middlePan) return "grabbing";
    if (state.tool === "pan" || state.spacePan) return "grab";
    return "default";
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
    refs.stageHost.style.cursor = "grabbing";
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
    refs.stageHost.style.cursor = spriteCursor();
  }

  function handleStageDown(event) {
    if (event.evt?.button === 1 || state.middlePan) return;
    if (state.tool === "pan" || state.spacePan) {
      state.stage.draggable(true);
      refs.stageHost.style.cursor = "grabbing";
      return;
    }
    if (state.tool !== "select" || !state.image || event.target !== state.imageNode) return;
    const pointer = stagePointer();
    if (!pointer) return;
    const col = Math.floor(pointer.x / frameWidth());
    const row = Math.floor(pointer.y / frameHeight());
    const index = row * columns() + col;
    if (index >= 0 && index < frameCount()) {
      state.selectedFrame = index;
      syncInputsFromState();
      renderAll();
    }
  }

  function handleStageUp() {
    state.stage.draggable(state.tool === "pan" || state.spacePan);
    refs.stageHost.style.cursor = spriteCursor();
  }

  function handleKeyDown(event) {
    if (["ControlLeft", "ControlRight", "MetaLeft", "MetaRight", "AltLeft", "AltRight"].includes(event.code)) {
      setCenterScaleModifier(hasCenterScaleModifier(event));
    }
    if (["ShiftLeft", "ShiftRight"].includes(event.code)) {
      setRatioLockModifier(hasRatioLockModifier(event));
    }
    if (isTypingTarget(event.target)) return;
    if (event.code === "Space") {
      event.preventDefault();
      state.spacePan = true;
      state.stage.draggable(true);
      refs.stageHost.style.cursor = spriteCursor();
    }
  }

  function handleKeyUp(event) {
    if (["ControlLeft", "ControlRight", "MetaLeft", "MetaRight", "AltLeft", "AltRight"].includes(event.code)) {
      setCenterScaleModifier(hasCenterScaleModifier(event));
    }
    if (["ShiftLeft", "ShiftRight"].includes(event.code)) {
      setRatioLockModifier(hasRatioLockModifier(event));
    }
    if (event.code !== "Space") return;
    state.spacePan = false;
    state.stage.draggable(state.tool === "pan");
    refs.stageHost.style.cursor = spriteCursor();
  }

  function isTypingTarget(target) {
    if (!(target instanceof HTMLElement)) return false;
    return ["INPUT", "TEXTAREA", "SELECT"].includes(target.tagName) || target.isContentEditable;
  }

  function handleContextMenu(event) {
    event.evt.preventDefault();
    Editor.openContextMenu([
      { label: "เลือกเฟรมนี้", icon: "fa-solid fa-crosshairs", action: () => handleStageDown({ ...event, target: state.imageNode }) },
      { label: "รีเซ็ตกรอบตัด", icon: "fa-solid fa-crop-simple", action: resetCrop },
      { label: "ตัดขอบอัตโนมัติ", icon: "fa-solid fa-scissors", action: autoTrim },
      { label: "ปรับให้เห็นทั้งหมด", icon: "fa-solid fa-expand", action: () => fitView() },
      { label: state.playing ? "หยุดพรีวิว" : "เล่นพรีวิว", icon: state.playing ? "fa-solid fa-pause" : "fa-solid fa-play", action: togglePreview }
    ], event.evt.clientX, event.evt.clientY);
  }

  function fitView(redraw = true) {
    if (!state.stage || !state.image) return;
    const padding = 36;
    const scaleX = (state.stage.width() - padding * 2) / Math.max(1, state.image.naturalWidth);
    const scaleY = (state.stage.height() - padding * 2) / Math.max(1, state.image.naturalHeight);
    const scale = Editor.clamp(Math.min(scaleX, scaleY), 0.05, 4);
    state.stage.scale({ x: scale, y: scale });
    state.stage.position({
      x: (state.stage.width() - state.image.naturalWidth * scale) / 2,
      y: (state.stage.height() - state.image.naturalHeight * scale) / 2
    });
    if (redraw) state.stage.batchDraw();
    refreshStatus();
  }

  function drawImageNode() {
    state.layers.image.destroyChildren();
    state.layers.grid.destroyChildren();
    if (refs.emptyState) refs.emptyState.hidden = Boolean(state.image);
    if (!state.image) {
      state.imageNode = null;
      state.layers.image.batchDraw();
      state.layers.grid.batchDraw();
      return;
    }
    state.imageNode = new Konva.Image({
      image: state.image,
      x: 0,
      y: 0,
      width: state.image.naturalWidth,
      height: state.image.naturalHeight
    });
    state.layers.image.add(state.imageNode);
    drawGrid();
    state.layers.image.batchDraw();
  }

  function drawGrid() {
    if (!state.image) return;
    const fw = frameWidth();
    const fh = frameHeight();
    const grid = new Konva.Group({ listening: false });
    for (let col = 1; col < columns(); col += 1) {
      grid.add(new Konva.Line({
        points: [col * fw, 0, col * fw, state.image.naturalHeight],
        stroke: "rgba(255, 255, 255, 0.34)",
        strokeWidth: 1,
        dash: [6, 6]
      }));
    }
    for (let row = 1; row < rows(); row += 1) {
      grid.add(new Konva.Line({
        points: [0, row * fh, state.image.naturalWidth, row * fh],
        stroke: "rgba(255, 255, 255, 0.34)",
        strokeWidth: 1,
        dash: [6, 6]
      }));
    }
    const frame = frameRect();
    grid.add(new Konva.Rect({
      x: frame.x,
      y: frame.y,
      width: frame.width,
      height: frame.height,
      stroke: "#8ea1ff",
      strokeWidth: 2,
      dash: [10, 6]
    }));
    state.layers.grid.add(grid);
    state.layers.grid.batchDraw();
  }

  function syncCropRectFromState() {
    if (!state.cropRect || !state.image) return;
    state.crop = normalizeCrop(state.crop);
    const frame = frameRect();
    state.cropRect.setAttrs({
      visible: true,
      x: frame.x + state.crop.x,
      y: frame.y + state.crop.y,
      width: state.crop.width,
      height: state.crop.height,
      scaleX: 1,
      scaleY: 1
    });
    syncTransformerModifiers();
    state.transformer.nodes([state.cropRect]);
    state.layers.crop.batchDraw();
  }

  function handleTransformStart(event) {
    if (!state.cropRect) return;
    const centered = hasCenterScaleModifier(event?.evt);
    const keepRatio = hasRatioLockModifier(event?.evt);
    state.transformSession = {
      type: "transform",
      centered,
      keepRatio,
      startBox: {
        width: state.cropRect.width(),
        height: state.cropRect.height()
      },
      ratio: Math.max(0.0001, state.cropRect.width() / Math.max(1, state.cropRect.height())),
      center: {
        x: state.cropRect.x() + (state.cropRect.width() / 2),
        y: state.cropRect.y() + (state.cropRect.height() / 2)
      }
    };
    syncTransformerModifiers(centered, keepRatio);
  }

  function handleTransformEnd() {
    commitCropRect();
    state.transformSession = null;
    syncTransformerModifiers();
  }

  function clampLocalCropBox(box) {
    if (!state.image) return normalizedBox(box);
    const frame = frameRect();
    const next = normalizedBox(box);
    let width = Editor.clamp(next.width, 1, frame.width);
    let height = Editor.clamp(next.height, 1, frame.height);
    let x = next.x;
    let y = next.y;
    const keepRatio = Boolean(state.transformSession?.keepRatio);
    const baseRatio = Math.max(0.0001, Number(state.transformSession?.ratio || (next.width / Math.max(1, next.height)) || 1));

    if (keepRatio) {
      const startWidth = Math.max(1, Number(state.transformSession?.startBox?.width || next.width || width));
      const startHeight = Math.max(1, Number(state.transformSession?.startBox?.height || next.height || height));
      const widthDelta = Math.abs(width - startWidth);
      const heightDelta = Math.abs(height - startHeight);
      if (widthDelta >= heightDelta) {
        height = width / baseRatio;
      } else {
        width = height * baseRatio;
      }
      if (width > frame.width) {
        width = frame.width;
        height = width / baseRatio;
      }
      if (height > frame.height) {
        height = frame.height;
        width = height * baseRatio;
      }
      width = Editor.clamp(width, 1, frame.width);
      height = Editor.clamp(height, 1, frame.height);
    }

    if (state.transformSession?.centered) {
      const center = state.transformSession.center || {
        x: next.x + (next.width / 2),
        y: next.y + (next.height / 2)
      };
      const maxHalfWidth = Math.max(0.5, Math.min(center.x - frame.x, (frame.x + frame.width) - center.x));
      const maxHalfHeight = Math.max(0.5, Math.min(center.y - frame.y, (frame.y + frame.height) - center.y));
      width = Math.min(width, maxHalfWidth * 2);
      height = Math.min(height, maxHalfHeight * 2);
      x = center.x - (width / 2);
      y = center.y - (height / 2);
    }

    x = Editor.clamp(x, frame.x, frame.x + frame.width - width);
    y = Editor.clamp(y, frame.y, frame.y + frame.height - height);
    return { x, y, width, height, rotation: 0 };
  }

  function cropBoxFromNode() {
    if (!state.image || !state.cropRect) return null;
    return normalizedBox({
      x: state.cropRect.x(),
      y: state.cropRect.y(),
      width: state.cropRect.width(),
      height: state.cropRect.height(),
      rotation: 0
    });
  }

  function transformedCropBoxFromNode() {
    const box = cropBoxFromNode();
    if (!box || !state.cropRect) return null;
    return normalizedBox({
      x: box.x,
      y: box.y,
      width: Math.max(1, box.width * Math.abs(state.cropRect.scaleX() || 1)),
      height: Math.max(1, box.height * Math.abs(state.cropRect.scaleY() || 1)),
      rotation: 0
    });
  }

  function syncCropStateFromBox(box, redrawPreview = true) {
    if (!box || !state.image) return null;
    const frame = frameRect();
    state.crop = normalizeCrop({
      x: Math.round(box.x - frame.x),
      y: Math.round(box.y - frame.y),
      width: Math.round(box.width),
      height: Math.round(box.height)
    });
    state.cropMode = "manual";
    syncInputsFromState();
    if (redrawPreview) {
      drawPreview();
      drawResultPreview();
    }
    return state.crop;
  }

  function previewCropTransform() {
    const box = clampLocalCropBox(transformedCropBoxFromNode());
    if (!box || !state.cropRect) return;
    state.cropRect.setAttrs({
      x: box.x,
      y: box.y,
      width: box.width,
      height: box.height,
      scaleX: 1,
      scaleY: 1
    });
    syncCropStateFromBox(box, true);
    state.layers.crop.batchDraw();
  }

  function constrainCropRect() {
    if (!state.image || !state.cropRect) return;
    const nextBox = clampLocalCropBox(cropBoxFromNode());
    if (!nextBox) return;
    state.cropRect.setAttrs({
      x: nextBox.x,
      y: nextBox.y,
      width: nextBox.width,
      height: nextBox.height,
      scaleX: 1,
      scaleY: 1
    });
    commitCropRect(false);
  }

  function commitCropRect(redraw = true) {
    if (!state.image || !state.cropRect) return;
    const box = clampLocalCropBox(transformedCropBoxFromNode() || {
      x: state.cropRect.x(),
      y: state.cropRect.y(),
      width: state.cropRect.width(),
      height: state.cropRect.height(),
      rotation: 0
    });
    syncCropStateFromBox(box, redraw);
    state.cropRect.setAttrs({
      x: box.x,
      y: box.y,
      width: box.width,
      height: box.height,
      scaleX: 1,
      scaleY: 1
    });
    if (redraw) {
      syncCropRectFromState();
    } else {
      state.layers.crop.batchDraw();
    }
  }

  function resetCrop() {
    state.crop = { x: 0, y: 0, width: frameWidth(), height: frameHeight() };
    state.cropMode = "manual";
    refs.displayWidthInput.value = state.crop.width;
    refs.displayHeightInput.value = state.crop.height;
    renderAll();
  }

  function settingsChanged() {
    if (!state.image) {
      refreshStatus();
      return;
    }
    refs.frameCountInput.value = frameCount();
    refs.outputColumnsInput.value = outputColumns();
    state.selectedFrame = Editor.clamp(intValue(refs.selectedFrameInput, state.selectedFrame, 0, frameCount() - 1), 0, frameCount() - 1);
    state.previewFrame = Editor.clamp(state.previewFrame, 0, frameCount() - 1);
    state.crop = normalizeCrop(state.crop);
    renderAll();
  }

  function syncInputsFromState() {
    refs.selectedFrameInput.value = state.selectedFrame;
    refs.cropXInput.value = state.crop.x;
    refs.cropYInput.value = state.crop.y;
    refs.cropWidthInput.value = state.crop.width;
    refs.cropHeightInput.value = state.crop.height;
    refs.frameSizeLabel.textContent = state.image ? `${frameWidth()} x ${frameHeight()}` : "-";
    refs.cropSizeLabel.textContent = `${state.crop.width} x ${state.crop.height}`;
    refreshStatus();
  }

  function syncCropFromInputs(event = null) {
    const target = event?.target || null;
    const baseCrop = normalizeCrop(state.crop);
    const nextX = intValue(refs.cropXInput, state.crop.x, 0, frameWidth());
    const nextY = intValue(refs.cropYInput, state.crop.y, 0, frameHeight());
    const nextWidth = intValue(refs.cropWidthInput, state.crop.width, 1, frameWidth());
    const nextHeight = intValue(refs.cropHeightInput, state.crop.height, 1, frameHeight());

    if (target === refs.cropWidthInput || target === refs.cropHeightInput) {
      state.crop = anchoredCropFromDimensions(baseCrop, nextWidth, nextHeight);
    } else {
      state.crop = normalizeCrop({
        x: nextX,
        y: nextY,
        width: nextWidth,
        height: nextHeight
      });
    }
    state.cropMode = "manual";
    renderAll();
  }

  function renderAll() {
    if (!state.stage) return;
    state.layers.grid.destroyChildren();
    drawGrid();
    syncCropRectFromState();
    syncInputsFromState();
    drawPreview();
    drawResultPreview();
  }

  function refreshStatus() {
    refs.exportButton.disabled = !canExport || !state.file || !state.image;
    refs.playButton.disabled = !state.image;
    refs.autoTrimButton.disabled = !state.image;
    refs.resetCropButton.disabled = !state.image;
    refs.copyPathButton.disabled = !state.result?.path;
    refs.copyConfigButton.disabled = !state.resultConfig;
    refs.sendConfigButton.disabled = !state.resultConfig;
    if (refs.copySingleFrameConfigButton) refs.copySingleFrameConfigButton.disabled = !canExport || !state.file || !state.image;
    if (refs.sendSingleFrameConfigButton) refs.sendSingleFrameConfigButton.disabled = !canExport || !state.file || !state.image;
    refs.zoomLabel.textContent = `${Math.round((state.stage?.scaleX() || 1) * 100)}%`;
    refs.resultLabel.textContent = state.result ? `${state.result.sheetWidth || state.result.width} x ${state.result.sheetHeight || state.result.height}` : "-";
  }

  function buildSourceCanvas() {
    if (!state.image) return;
    const canvas = document.createElement("canvas");
    canvas.width = state.image.naturalWidth;
    canvas.height = state.image.naturalHeight;
    const context = canvas.getContext("2d", { willReadFrequently: true });
    context.drawImage(state.image, 0, 0);
    state.sourceCanvas = canvas;
    state.sourceContext = context;
  }

  function loadFile(file) {
    if (!file) return;
    if (!/^image\/(png|jpe?g|webp)$/i.test(file.type)) {
      Editor.toast("ใช้ไฟล์ PNG, JPG หรือ WebP", "bad");
      return;
    }
    if (state.imageUrl) URL.revokeObjectURL(state.imageUrl);
    state.file = file;
    state.imageUrl = URL.createObjectURL(file);
    const image = new Image();
    image.onload = () => {
      state.image = image;
      state.selectedFrame = 0;
      refs.columnsInput.value = refs.columnsInput.value || 1;
      refs.rowsInput.value = refs.rowsInput.value || 1;
      refs.frameCountInput.value = Math.max(1, columns() * rows());
      refs.outputColumnsInput.value = String(Editor.autoGridColumns(frameCount()));
      buildSourceCanvas();
      drawImageNode();
      resetCrop();
      fitView();
      setStatus(`${image.naturalWidth} x ${image.naturalHeight}`, "good");
      Editor.toast("โหลด Sprite แล้ว", "good");
    };
    image.onerror = () => {
      Editor.toast("โหลดรูปนี้ไม่ได้", "bad");
    };
    image.src = state.imageUrl;
  }

  async function loadAssets() {
    if (!refs.assetList) return;
    refs.assetList.innerHTML = '<div class="editor-note">กำลังโหลดคลังไฟล์...</div>';
    try {
      const response = await fetch("mileage-api.php?action=list_assets&boardCode=main", {
        credentials: "same-origin",
        cache: "no-store"
      });
      const data = await response.json();
      if (!response.ok || !data.ok) throw new Error(data.message || data.code || "โหลดคลังไฟล์ไม่สำเร็จ");
      state.assets = Array.isArray(data.assets) ? data.assets : [];
      state.assetsLoaded = true;
      if (!state.selectedAssetPath && state.assets[0]?.path) {
        state.selectedAssetPath = String(state.assets[0].path || "");
      }
      renderAssets();
    } catch (error) {
      refs.assetList.innerHTML = `<div class="editor-note">โหลดคลังไฟล์ไม่สำเร็จ: ${Editor.escapeHtml(error.message || "")}</div>`;
      if (refs.assetPreview) {
        refs.assetPreview.innerHTML = `<div class="editor-note">โหลดตัวอย่างไฟล์ไม่ได้: ${Editor.escapeHtml(error.message || "")}</div>`;
      }
    }
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

  function setSelectedAsset(path) {
    state.selectedAssetPath = String(path || "").trim();
    renderAssets();
  }

  function renderAssetPreview() {
    if (!refs.assetPreview) return;
    const asset = selectedAssetRecord();
    if (!asset) {
      refs.assetPreview.innerHTML = '<div class="editor-note">ยังไม่พบไฟล์ที่ตรงกับคำค้น</div>';
      refs.assetPreview.classList.remove("is-picking");
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
            <button class="ui tiny primary button" type="button" data-asset-use="${path}"><i class="fa-solid fa-check"></i> ใช้รูปนี้</button>
            <a class="ui tiny button" href="${path}" target="_blank" rel="noopener"><i class="fa-solid fa-arrow-up-right-from-square"></i> เปิดไฟล์</a>
          </div>
        </div>
      </div>
    `;
  }

  function renderAssets() {
    if (!refs.assetList) return;
    const assets = filteredAssets().slice(0, 120);
    if (!assets.find((asset) => String(asset.path || "") === String(state.selectedAssetPath || ""))) {
      state.selectedAssetPath = String(assets[0]?.path || "");
    }
    refs.assetList.classList.toggle("is-grid", state.assetView === "grid");
    refs.assetList.classList.toggle("is-list", state.assetView === "list");
    refs.assetList.innerHTML = assets.map((asset) => {
      const path = Editor.escapeHtml(asset.path || "");
      const name = Editor.escapeHtml(asset.name || asset.path || "");
      return `
        <button class="editor-asset-card${String(asset.path || "") === String(state.selectedAssetPath || "") ? " is-active" : ""}" type="button" data-source-asset="${path}">
          <span class="editor-asset-thumb"><img src="${path}" alt="${name}" loading="lazy"></span>
          <span class="editor-asset-copy">
            <strong class="editor-asset-title">${name}</strong>
            <span class="editor-asset-meta">${Number(asset.width || 0)} x ${Number(asset.height || 0)}</span>
            <span class="editor-asset-path">${path}</span>
          </span>
        </button>
      `;
    }).join("") || '<div class="editor-note">ยังไม่พบรูปในคลังไฟล์</div>';
    renderAssetPreview();
  }

  async function loadSourceAsset(path) {
    const sourcePath = String(path || "").trim();
    if (!sourcePath) return;
    try {
      setStatus("กำลังโหลดจากคลัง", "warn");
      state.selectedAssetPath = sourcePath;
      const response = await fetch(sourcePath, { credentials: "same-origin", cache: "no-store" });
      if (!response.ok) throw new Error(`HTTP ${response.status}`);
      const blob = await response.blob();
      const name = sourcePath.split("/").pop() || "sprite-source.png";
      const file = new File([blob], name, { type: blob.type || "image/png" });
      loadFile(file);
      Editor.toast("โหลด source จากคลังแล้ว", "good");
    } catch (error) {
      setStatus("โหลดจากคลังไม่สำเร็จ", "warn");
      Editor.toast(error.message || "โหลดจากคลังไม่สำเร็จ", "bad");
    }
  }

  function autoTrim() {
    if (!state.image || !state.sourceContext) return;
    const fw = frameWidth();
    const fh = frameHeight();
    const threshold = intValue(refs.alphaThresholdInput, 120, 1, 127);
    const padding = intValue(refs.paddingInput, 0, 0, 512);
    let minX = fw;
    let minY = fh;
    let maxX = -1;
    let maxY = -1;
    for (let frame = 0; frame < frameCount(); frame += 1) {
      const rect = frameRect(frame);
      const data = state.sourceContext.getImageData(rect.x, rect.y, fw, fh).data;
      for (let y = 0; y < fh; y += 1) {
        for (let x = 0; x < fw; x += 1) {
          const alpha = data[((y * fw + x) * 4) + 3];
          if (alpha <= threshold) continue;
          minX = Math.min(minX, x);
          minY = Math.min(minY, y);
          maxX = Math.max(maxX, x);
          maxY = Math.max(maxY, y);
        }
      }
    }
    if (maxX < minX || maxY < minY) {
      resetCrop();
      return;
    }
    state.crop = normalizeCrop({
      x: Math.max(0, minX - padding),
      y: Math.max(0, minY - padding),
      width: Math.min(fw, maxX + padding + 1) - Math.max(0, minX - padding),
      height: Math.min(fh, maxY + padding + 1) - Math.max(0, minY - padding)
    });
    state.cropMode = "auto-alpha";
    refs.displayWidthInput.value = state.crop.width;
    refs.displayHeightInput.value = state.crop.height;
    renderAll();
  }

  function frameForTime(ts) {
    const count = frameCount();
    const frame = Math.floor((ts / 1000) * fps());
    if (mode() === "once") return Math.min(count - 1, frame);
    if (mode() === "pingpong" && count > 1) {
      const cycle = count * 2 - 2;
      const position = frame % cycle;
      return position < count ? position : cycle - position;
    }
    return frame % count;
  }

  function drawPreview() {
    const canvas = refs.previewCanvas;
    const ctx = canvas.getContext("2d");
    const dpr = Math.max(1, Math.min(window.devicePixelRatio || 1, 2));
    const cssSize = Math.max(120, Math.round(canvas.getBoundingClientRect().width || 180));
    if (canvas.width !== cssSize * dpr || canvas.height !== cssSize * dpr) {
      canvas.width = cssSize * dpr;
      canvas.height = cssSize * dpr;
    }
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    ctx.clearRect(0, 0, cssSize, cssSize);
    drawPreviewBackground(ctx, cssSize);
    if (!state.image) return;
    const frame = frameRect(state.previewFrame);
    const crop = normalizeCrop();
    const scale = Math.min((cssSize - 24) / crop.width, (cssSize - 24) / crop.height);
    const width = crop.width * scale;
    const height = crop.height * scale;
    ctx.imageSmoothingEnabled = false;
    ctx.drawImage(
      state.image,
      frame.x + crop.x,
      frame.y + crop.y,
      crop.width,
      crop.height,
      (cssSize - width) / 2,
      (cssSize - height) / 2,
      width,
      height
    );
  }

  function drawResultPreview() {
    const canvas = refs.resultPreviewCanvas;
    if (!canvas) return;
    const ctx = canvas.getContext("2d");
    const dpr = Math.max(1, Math.min(window.devicePixelRatio || 1, 2));
    const cssSize = Math.max(120, Math.round(canvas.getBoundingClientRect().width || 180));
    if (canvas.width !== cssSize * dpr || canvas.height !== cssSize * dpr) {
      canvas.width = cssSize * dpr;
      canvas.height = cssSize * dpr;
    }
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    ctx.clearRect(0, 0, cssSize, cssSize);
    drawPreviewBackground(ctx, cssSize);

    const image = state.resultImage || state.image;
    if (!image) return;
    const config = state.resultConfig || {
      columns: columns(),
      rows: rows(),
      frameWidth: normalizeCrop().width,
      frameHeight: normalizeCrop().height,
      frameCount: frameCount(),
      fps: fps(),
      mode: mode()
    };
    const frame = frameForTime(performance.now());
    const outputColumns = Math.max(1, Number(config.columns || 1));
    const frameWidth = Math.max(1, Number(config.frameWidth || normalizeCrop().width || 1));
    const frameHeight = Math.max(1, Number(config.frameHeight || normalizeCrop().height || 1));
    const sourceX = state.resultImage
      ? (frame % outputColumns) * frameWidth
      : frameRect(frame).x + normalizeCrop().x;
    const sourceY = state.resultImage
      ? Math.floor(frame / outputColumns) * frameHeight
      : frameRect(frame).y + normalizeCrop().y;
    const scale = Math.min((cssSize - 24) / frameWidth, (cssSize - 24) / frameHeight);
    const width = frameWidth * scale;
    const height = frameHeight * scale;
    ctx.imageSmoothingEnabled = false;
    ctx.drawImage(
      image,
      sourceX,
      sourceY,
      frameWidth,
      frameHeight,
      (cssSize - width) / 2,
      (cssSize - height) / 2,
      width,
      height
    );
  }

  function previewLoop(ts) {
    if (state.image && state.playing) {
      state.previewFrame = frameForTime(ts);
      drawPreview();
      drawResultPreview();
    }
    window.requestAnimationFrame(previewLoop);
  }

  function drawPreviewBackground(ctx, size) {
    const mode = refs.previewBgInput?.value || "dark";
    if (mode === "transparent") return;
    if (mode === "white" || mode === "black") {
      ctx.fillStyle = mode === "white" ? "#ffffff" : "#000000";
      ctx.fillRect(0, 0, size, size);
      return;
    }
    if (mode === "checker") {
      const tile = 12;
      for (let y = 0; y < size; y += tile) {
        for (let x = 0; x < size; x += tile) {
          ctx.fillStyle = ((x / tile + y / tile) % 2 === 0) ? "#ffffff" : "#d8dde8";
          ctx.fillRect(x, y, tile, tile);
        }
      }
      return;
    }
    ctx.fillStyle = "rgba(5, 10, 18, 0.82)";
    ctx.fillRect(0, 0, size, size);
  }

  function togglePreview() {
    state.playing = !state.playing;
    refs.playButton.innerHTML = state.playing ? '<i class="fa-solid fa-pause"></i>' : '<i class="fa-solid fa-play"></i>';
    drawPreview();
  }

  function mileageConfigFromResult(data) {
    return {
      src: data.path,
      columns: Number(data.columns || outputColumns()),
      rows: Number(data.rows || Math.ceil(frameCount() / outputColumns())),
      frameWidth: Number(data.frameWidth || state.crop.width),
      frameHeight: Number(data.frameHeight || state.crop.height),
      frameCount: Number(data.frameCount || frameCount()),
      fps: Number(data.fps || fps()),
      mode: String(data.mode || mode()),
      width: Number(data.displayWidth || displayWidth()),
      height: Number(data.displayHeight || displayHeight())
    };
  }

  async function exportSprite(exportMode = "sheet") {
    if (!state.file || !state.image || !canExport) return;
    const modeName = exportMode === "single" ? "single" : "sheet";
    const crop = normalizeCrop();
    const form = new FormData();
    form.append("image", state.file);
    form.append("exportMode", modeName);
    form.append("columns", String(columns()));
    form.append("rows", String(rows()));
    form.append("frameCount", String(frameCount()));
    form.append("selectedFrame", String(state.selectedFrame));
    form.append("outputColumns", String(outputColumns()));
    form.append("cropMode", state.cropMode);
    form.append("cropX", String(crop.x));
    form.append("cropY", String(crop.y));
    form.append("cropWidth", String(crop.width));
    form.append("cropHeight", String(crop.height));
    form.append("alphaThreshold", String(intValue(refs.alphaThresholdInput, 120, 1, 127)));
    form.append("padding", String(intValue(refs.paddingInput, 0, 0, 512)));
    form.append("fps", String(fps()));
    form.append("mode", mode());
    form.append("displayWidth", String(displayWidth()));
    form.append("displayHeight", String(displayHeight()));
    refs.exportButton.disabled = true;
    setStatus(modeName === "single" ? "กำลังส่งออกเฟรมเดียว" : "กำลังส่งออก", "warn");
    try {
      const response = await fetch("sprite.php?action=export", {
        method: "POST",
        headers: { "X-CSRF-Token": csrfToken },
        body: form
      });
      const data = await response.json();
      if (!response.ok || !data.ok) throw new Error(data.message || "ส่งออกไม่สำเร็จ");
      state.result = data;
      state.resultConfig = data.mileageConfig || mileageConfigFromResult(data);
      refs.resultPreview.innerHTML = `<img src="${Editor.escapeHtml(data.path)}?v=${Date.now()}" alt="Sprite sheet ที่ส่งออกแล้ว">`;
      const resultImage = new Image();
      state.resultImage = null;
      state.resultImageUrl = `${data.path}?v=${Date.now()}`;
      resultImage.onload = () => {
        state.resultImage = resultImage;
        drawResultPreview();
      };
      resultImage.src = state.resultImageUrl;
      refs.pathOutput.value = data.path || "";
      refs.configOutput.value = JSON.stringify(state.resultConfig, null, 2);
      setStatus("ส่งออกแล้ว", "good");
      Editor.toast(modeName === "single" ? "ส่งออกเฟรมเดียวแล้ว" : "ส่งออก Sprite แล้ว", "good");
      return state.resultConfig;
    } catch (error) {
      setStatus("ส่งออกไม่สำเร็จ", "warn");
      Editor.toast(error.message || "ส่งออกไม่สำเร็จ", "bad");
      return null;
    } finally {
      refreshStatus();
    }
  }

  async function copyOutput(kind) {
    const value = kind === "path" ? refs.pathOutput.value : refs.configOutput.value;
    if (!value) return;
    await Editor.copyText(value);
  }

  function sendMileageConfig() {
    if (!state.resultConfig) return;
    localStorage.setItem("mileageSpriteConfig", JSON.stringify(state.resultConfig));
    localStorage.setItem("mileageSpriteConfigAt", String(Date.now()));
    Editor.toast("ส่ง config ไปหน้า Mileage แล้ว", "good");
  }

  async function exportSingleFrameAndThen(action) {
    const config = await exportSprite("single");
    if (!config) return;
    if (action === "copy") {
      await Editor.copyText(JSON.stringify(config, null, 2));
      return;
    }
    if (action === "send") {
      localStorage.setItem("mileageSpriteConfig", JSON.stringify(config));
      localStorage.setItem("mileageSpriteConfigAt", String(Date.now()));
      Editor.toast("ส่งเฟรมเดียวไปหน้า Mileage แล้ว", "good");
    }
  }

  function bindEvents() {
    Editor.installTooltips(document);
    refs.toolButtons.forEach((button) => button.addEventListener("click", () => setTool(button.dataset.spriteTool || "select")));
    refs.fileInput.addEventListener("change", () => loadFile(refs.fileInput.files?.[0] || null));
    refs.dropZone.addEventListener("click", () => refs.fileInput.click());
    refs.dropZone.addEventListener("dragover", (event) => {
      event.preventDefault();
      refs.dropZone.classList.add("is-dragover");
    });
    refs.dropZone.addEventListener("dragleave", () => refs.dropZone.classList.remove("is-dragover"));
    refs.dropZone.addEventListener("drop", (event) => {
      event.preventDefault();
      refs.dropZone.classList.remove("is-dragover");
      loadFile(event.dataTransfer?.files?.[0] || null);
    });
    refs.assetButton?.addEventListener("click", () => {
      if (state.assetsLoaded) {
        renderAssets();
        return;
      }
      loadAssets();
    });
    refs.assetSearchInput?.addEventListener("input", () => {
      state.assetSearch = refs.assetSearchInput.value || "";
      renderAssets();
    });
    refs.assetViewButtons.forEach((button) => {
      button.addEventListener("click", () => {
        state.assetView = button.dataset.spriteAssetView === "list" ? "list" : "grid";
        refs.assetViewButtons.forEach((item) => item.classList.toggle("is-active", item === button));
        renderAssets();
      });
    });
    refs.assetList?.addEventListener("click", (event) => {
      const row = event.target.closest("[data-source-asset]");
      if (!row) return;
      setSelectedAsset(row.dataset.sourceAsset || "");
    });
    refs.assetList?.addEventListener("dblclick", (event) => {
      const row = event.target.closest("[data-source-asset]");
      if (!row) return;
      loadSourceAsset(row.dataset.sourceAsset || "");
    });
    refs.assetPreview?.addEventListener("click", (event) => {
      const button = event.target.closest("[data-asset-use]");
      if (!button) return;
      loadSourceAsset(button.dataset.assetUse || state.selectedAssetPath || "");
    });
    refs.fitButton.addEventListener("click", () => fitView());
    refs.resetCropButton.addEventListener("click", resetCrop);
    refs.autoTrimButton.addEventListener("click", autoTrim);
    refs.autoGridButton?.addEventListener("click", () => {
      refs.outputColumnsInput.value = String(Editor.autoGridColumns(frameCount()));
      settingsChanged();
    });
    refs.playButton.addEventListener("click", togglePreview);
    refs.previewBgInput?.addEventListener("change", () => {
      drawPreview();
      drawResultPreview();
    });
    refs.exportButton.addEventListener("click", () => exportSprite("sheet"));
    refs.copyPathButton.addEventListener("click", () => copyOutput("path"));
    refs.copyConfigButton.addEventListener("click", () => copyOutput("config"));
    refs.sendConfigButton.addEventListener("click", sendMileageConfig);
    refs.copySingleFrameConfigButton?.addEventListener("click", () => exportSingleFrameAndThen("copy"));
    refs.sendSingleFrameConfigButton?.addEventListener("click", () => exportSingleFrameAndThen("send"));
    refs.cropAnchorButtons.forEach((button) => {
      button.addEventListener("click", () => setCropAnchor(button.dataset.cropAnchor || "center"));
    });
    [refs.columnsInput, refs.rowsInput, refs.frameCountInput].forEach((input) => {
      input.addEventListener("input", () => {
        refs.outputColumnsInput.value = String(Editor.autoGridColumns(frameCount()));
        settingsChanged();
      });
      input.addEventListener("change", () => {
        refs.outputColumnsInput.value = String(Editor.autoGridColumns(frameCount()));
        settingsChanged();
      });
    });
    [refs.outputColumnsInput, refs.fpsInput, refs.modeInput].forEach((input) => {
      input.addEventListener("change", settingsChanged);
      input.addEventListener("input", settingsChanged);
    });
    [refs.displayWidthInput, refs.displayHeightInput, refs.alphaThresholdInput, refs.paddingInput].forEach((input) => {
      input.addEventListener("change", () => {
        state.resultConfig = null;
        refreshStatus();
      });
      input.addEventListener("input", () => {
        state.resultConfig = null;
        refreshStatus();
      });
    });
    refs.selectedFrameInput.addEventListener("change", () => {
      state.selectedFrame = Editor.clamp(intValue(refs.selectedFrameInput, state.selectedFrame, 0, frameCount() - 1), 0, frameCount() - 1);
      renderAll();
    });
    [refs.cropXInput, refs.cropYInput, refs.cropWidthInput, refs.cropHeightInput].forEach((input) => {
      input.addEventListener("change", syncCropFromInputs);
      input.addEventListener("input", syncCropFromInputs);
    });
    window.addEventListener("blur", () => {
      state.spacePan = false;
      state.transformSession = null;
      state.stage?.draggable(state.tool === "pan");
      refs.stageHost.style.cursor = spriteCursor();
      setCenterScaleModifier(false);
      setRatioLockModifier(false);
    });
  }

  function installDebugApi() {
    const host = String(window.location.hostname || "").trim().toLowerCase();
    const allow =
      host === "localhost"
      || host === "127.0.0.1"
      || host === "::1"
      || host.startsWith("192.168.")
      || host.startsWith("10.");
    if (!allow) return;
    window.__spriteEditorDebug = {
      setModifiers(options = {}) {
        if (Object.prototype.hasOwnProperty.call(options, "centered")) {
          setCenterScaleModifier(Boolean(options.centered));
        }
        if (Object.prototype.hasOwnProperty.call(options, "keepRatio")) {
          setRatioLockModifier(Boolean(options.keepRatio));
        }
      },
      clearModifiers() {
        setCenterScaleModifier(false);
        setRatioLockModifier(false);
      },
      getCrop() {
        return {
          ...state.crop,
          ratio: Number(state.crop.width || 1) / Math.max(1, Number(state.crop.height || 1))
        };
      },
      setAnchor(anchor) {
        setCropAnchor(anchor);
      }
    };
  }

  function bootEditor() {
    initStage();
    bindEvents();
    installDebugApi();
    setCropAnchor(state.cropAnchor);
    setTool("select");
    refs.exportButton.disabled = true;
    setStatus(canExport ? "พร้อมใช้งาน" : "ไม่มีสิทธิ์ส่งออก", canExport ? "good" : "warn");
    refreshStatus();
    loadAssets();
    window.requestAnimationFrame(previewLoop);
  }

  bootEditor();
})();
