<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/Bootstrap.php';
Bootstrap::init();

$boardCode = preg_replace('/[^a-z0-9_-]+/i', '-', strtolower(trim((string) ($_GET['boardCode'] ?? GachaMileageService::DEFAULT_BOARD_CODE)))) ?: GachaMileageService::DEFAULT_BOARD_CODE;
$playerToken = trim((string) ($_GET['player_token'] ?? ''));
$previewMode = in_array(strtolower(trim((string) ($_GET['preview'] ?? ''))), ['1', 'true', 'yes', 'on'], true);
$assetManifestRuntimeVersion = (string) (@filemtime(__DIR__ . '/assets/js/asset-manifest-runtime.js') ?: time());
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover" />
  <title>Mileage</title>
  <style>
    :root {
      --bg: #050716;
      --panel: rgba(8, 14, 34, 0.72);
      --line: rgba(144, 230, 255, 0.18);
      --ink: #eef7ff;
      --muted: rgba(230, 240, 255, 0.68);
      --accent: #86f1ff;
      --pink: #ff8edb;
      --warn: #ffd482;
      --good: #8effc9;
      --embed-bottom-offset: 0px;
      --embed-primary-offset: 0px;
      --font: "FC Vision Rounded", system-ui, sans-serif;
    }

    @font-face {
      font-family: "FC Vision Rounded";
      src: url("fonts/FCVisionRounded-Regular.woff2") format("woff2");
      font-weight: 400;
      font-style: normal;
      font-display: swap;
    }

    @font-face {
      font-family: "FC Vision Rounded";
      src: url("fonts/FCVisionRounded-SemiBold.woff2") format("woff2");
      font-weight: 600;
      font-style: normal;
      font-display: swap;
    }

    @font-face {
      font-family: "FC Vision Rounded";
      src: url("fonts/FCVisionRounded-Bold.woff2") format("woff2");
      font-weight: 700;
      font-style: normal;
      font-display: swap;
    }

    @font-face {
      font-family: "FC Vision Rounded";
      src: url("fonts/FCVisionRounded-Heavy.woff2") format("woff2");
      font-weight: 800;
      font-style: normal;
      font-display: swap;
    }

    @font-face {
      font-family: "FC Vision Rounded";
      src: url("fonts/FCVisionRounded-Black.woff2") format("woff2");
      font-weight: 900;
      font-style: normal;
      font-display: swap;
    }

    * {
      box-sizing: border-box;
      -webkit-tap-highlight-color: transparent;
      font-synthesis: none;
    }

    html,
    body {
      width: 100%;
      height: 100%;
      margin: 0;
      overflow: hidden;
      overscroll-behavior: none;
      touch-action: none;
      background:
        radial-gradient(circle at 50% 8%, rgba(68, 90, 210, 0.2), transparent 32%),
        linear-gradient(180deg, #090c20, #03050e 68%);
      color: var(--ink);
      font-family: var(--font);
    }

    body {
      position: fixed;
      inset: 0;
    }

    body.is-preview-mode .mileage-login-button,
    body.is-preview-mode .walk-action,
    body.is-preview-mode .boost-button {
      display: none !important;
    }

    button,
    input {
      font: inherit;
    }

    .viewer {
      position: fixed;
      inset: 0;
      overflow: hidden;
      background:
        linear-gradient(180deg, rgba(10, 18, 44, 0.34), rgba(2, 4, 12, 0.74)),
        radial-gradient(circle at 50% 28%, rgba(113, 98, 255, 0.16), transparent 34%);
      touch-action: none;
      user-select: none;
    }

    .viewer.has-overlay-open .hud-card,
    .viewer.has-overlay-open .self-button,
    .viewer.has-overlay-open .rank-button,
    .viewer.has-overlay-open .boost-button,
    .viewer.has-overlay-open .walk-action,
    .viewer.has-overlay-open .zoom-slider-wrap {
      opacity: 0;
      pointer-events: none;
    }

    .scene {
      position: absolute;
      inset: 0;
      overflow: hidden;
      cursor: grab;
      touch-action: none;
    }

    .scene.is-dragging {
      cursor: grabbing;
    }

    .board-shell {
      position: absolute;
      inset: 0;
      background: transparent;
      contain: layout paint style;
    }

    .board-shell.is-hidden {
      display: none;
    }

    .board-track,
    .board-overlay,
    .overlay-layer {
      display: none;
    }

    .board-canvas {
      position: absolute;
      inset: 0;
      width: 100%;
      height: 100%;
      display: block;
      pointer-events: none;
    }

    .board-track {
      overflow: hidden;
    }

    .board-track img {
      position: absolute;
      left: 0;
      width: 100%;
      height: auto;
      display: block;
      user-select: none;
      -webkit-user-drag: none;
      pointer-events: none;
    }

    .reward-marker,
    .step-badge,
    .avatar-marker,
    .cluster-badge {
      position: absolute;
    }

    .reward-marker,
    .step-badge,
    .cluster-badge {
      transform: translate(-50%, -50%);
    }

    .reward-marker {
      --reward-marker-size: 44px;
      width: var(--reward-marker-size);
      height: var(--reward-marker-size);
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 999px;
      border: calc(var(--reward-marker-size) * 0.085) solid var(--tone, #ffffff);
      background:
        radial-gradient(circle at 34% 24%, rgba(255, 255, 255, 0.34), transparent 27%),
        linear-gradient(180deg, rgba(28, 36, 72, 0.86), rgba(8, 12, 31, 0.82));
      color: var(--tone, #ffffff);
      font: 700 calc(var(--reward-marker-size) * 0.44)/1 var(--font);
      box-shadow:
        0 0 0 calc(var(--reward-marker-size) * 0.07) rgba(5, 7, 18, 0.28),
        0 calc(var(--reward-marker-size) * 0.28) calc(var(--reward-marker-size) * 0.72) rgba(0, 0, 0, 0.34),
        inset 0 calc(var(--reward-marker-size) * 0.08) calc(var(--reward-marker-size) * 0.15) rgba(255, 255, 255, 0.18);
      transform: translate(-50%, -50%) scale(var(--reward-marker-scale, 1));
      transform-origin: center;
      will-change: transform, opacity, filter;
    }

    .reward-marker span {
      display: grid;
      place-items: center;
      width: 72%;
      height: 72%;
      border-radius: 999px;
      background: rgba(255, 255, 255, 0.1);
      text-shadow: 0 2px 6px rgba(0, 0, 0, 0.38);
    }

    .reward-marker span img {
      display: block;
      width: 100%;
      height: 100%;
      object-fit: contain;
      border-radius: 999px;
      filter:
        drop-shadow(0 3px 6px rgba(0, 0, 0, 0.3))
        drop-shadow(0 0 7px rgba(255, 245, 183, 0.32));
    }

    .reward-marker.is-unlocked {
      pointer-events: auto;
      cursor: pointer;
      filter: drop-shadow(0 0 10px rgba(255, 244, 188, 0.34));
    }

    .reward-marker.is-claimed {
      opacity: 0.58;
      color: #f5fbff;
      border-color: rgba(235, 243, 255, 0.72);
      background: rgba(16, 24, 48, 0.76);
      filter: grayscale(0.18);
    }

    .reward-marker.is-just-claimed {
      animation: rewardClaimPop 1.05s cubic-bezier(0.22, 1, 0.36, 1);
    }

    .reward-marker.is-locked {
      opacity: 0.72;
      filter: grayscale(0.28);
    }

    .reward-marker.is-coin { --tone: #ffd581; }
    .reward-marker.is-ticket { --tone: #9ae9ff; }
    .reward-marker.is-gem { --tone: #ffd2ff; }
    .reward-marker.is-potion { --tone: #9cffbf; }
    .reward-marker.is-item { --tone: #b9a0ff; }

    .step-badge {
      min-width: var(--step-badge-size, 24px);
      height: var(--step-badge-size, 24px);
      padding: 0 6px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 999px;
      border: 1.5px solid rgba(235, 243, 255, 0.82);
      background: rgba(7, 11, 28, 0.34);
      color: #f5fbff;
      font-weight: 700;
      font-size: calc(var(--step-badge-size, 24px) * 0.46);
      line-height: 1;
      font-family: var(--font);
      letter-spacing: -0.02em;
      box-shadow:
        0 0 0 2px rgba(5, 7, 18, 0.22),
        0 6px 16px rgba(0, 0, 0, 0.22);
      backdrop-filter: blur(6px);
    }

    .step-badge.is-stack {
      width: auto;
      max-width: 58px;
      height: auto;
      min-height: 20px;
      padding: 3px 4px;
      display: flex;
      flex-wrap: wrap;
      gap: 2px;
      border-width: 1px;
      background: rgba(7, 11, 28, 0.28);
      font-size: 9px;
      line-height: 1;
    }

    .step-badge.is-stack span {
      min-width: 14px;
      height: 14px;
      display: inline-grid;
      place-items: center;
      border-radius: 999px;
      background: rgba(245, 251, 255, 0.12);
    }

    .sprite-marker {
      position: absolute;
      transform: translate(-50%, -50%);
      pointer-events: none;
      background-repeat: no-repeat;
      image-rendering: auto;
      filter: drop-shadow(0 8px 18px rgba(0, 0, 0, 0.28));
      will-change: background-position;
    }

    .avatar-marker {
      width: var(--marker-size, 20px);
      height: var(--marker-size, 20px);
      overflow: hidden;
      border-radius: 999px;
      background: #5d6f9c;
      border: 2px solid rgba(233, 243, 255, 0.94);
      box-shadow: 0 7px 18px rgba(0, 0, 0, 0.34);
      transform:
        translate(-50%, -50%)
        rotate(var(--marker-rotate, 0deg))
        scaleX(var(--marker-scale-x, 1))
        scaleY(var(--marker-scale-y, 1));
      transform-origin: center calc(100% + 12px);
      will-change: transform;
    }

    .avatar-marker.is-self {
      border-color: #ff9fe4;
      animation: selfPulse 1.4s ease-in-out infinite;
      box-shadow:
        0 0 0 3px rgba(255, 159, 228, 0.2),
        0 9px 24px rgba(0, 0, 0, 0.36);
    }

    .avatar-marker img {
      display: block;
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .avatar-fallback {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 100%;
      height: 100%;
      font-weight: 700;
      font-size: calc(var(--marker-size, 18px) * 0.42);
      line-height: 1;
      font-family: var(--font);
      color: #eef6ff;
    }

    .cluster-badge {
      min-width: 22px;
      height: 22px;
      padding: 0 6px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 999px;
      border: 2px solid rgba(240, 245, 255, 0.84);
      background: rgba(8, 13, 29, 0.94);
      color: #eef6ff;
      font: 700 10px/1 var(--font);
      box-shadow: 0 6px 16px rgba(0, 0, 0, 0.28);
    }

    .hud-card {
      position: fixed;
      z-index: 30;
      left: max(12px, env(safe-area-inset-left));
      top: max(12px, env(safe-area-inset-top));
      width: auto;
      max-width: calc(100vw - 84px);
      padding: 0;
      border: 0;
      border-radius: 0;
      background: transparent;
      box-shadow: none;
      backdrop-filter: none;
      pointer-events: none;
    }

    .hud-card h1,
    .hud-card p {
      display: none;
    }

    .hud-card h1 {
      margin: 0;
      font-size: 16px;
      line-height: 1.1;
      letter-spacing: 0.01em;
    }

    .hud-card p {
      margin: 6px 0 0;
      color: var(--muted);
      font-size: 12px;
      line-height: 1.45;
    }

    .summary-strip {
      display: flex;
      flex-wrap: wrap;
      gap: 5px;
      margin-top: 0;
    }

    .pill {
      padding: 5px 7px;
      border-radius: 999px;
      border: 1px solid rgba(255, 255, 255, 0.52);
      background: rgba(255, 242, 251, 0.42);
      color: rgba(59, 40, 82, 0.78);
      font-size: 10px;
      line-height: 1;
      white-space: nowrap;
      box-shadow: 0 10px 24px rgba(18, 12, 38, 0.12);
      backdrop-filter: blur(14px) saturate(1.04);
    }

    .pill strong {
      color: var(--ink);
      font-weight: 700;
    }

    .warn {
      color: var(--warn);
    }

    .good {
      color: var(--good);
    }

    .message-card {
      position: fixed;
      z-index: 50;
      left: 50%;
      top: 50%;
      width: min(360px, calc(100vw - 32px));
      transform: translate(-50%, -50%);
      padding: 18px;
      border: 1px solid rgba(141, 242, 255, 0.18);
      border-radius: 24px;
      background: rgba(8, 13, 31, 0.92);
      box-shadow: 0 24px 80px rgba(0, 0, 0, 0.44);
      backdrop-filter: blur(18px);
    }

    .message-card.is-hidden,
    .mileage-login-button.is-hidden,
    .leaderboard-panel.is-hidden,
    .toast.is-hidden,
    .boost-button.is-hidden,
    .walk-action.is-hidden {
      display: none;
    }

    .message-card h2 {
      margin: 0 0 8px;
      font-size: 17px;
    }

    .message-card p {
      margin: 0;
      color: var(--muted);
      font-size: 13px;
      line-height: 1.55;
    }

    .self-button {
      position: fixed;
      z-index: 34;
      right: max(16px, calc(env(safe-area-inset-right) + 16px));
      bottom: max(calc(var(--embed-bottom-offset) + 18px), calc(env(safe-area-inset-bottom) + var(--embed-bottom-offset) + 18px));
      width: 50px;
      height: 50px;
      display: grid;
      place-items: center;
      border: 1px solid rgba(255, 255, 255, 0.56);
      border-radius: 999px;
      background: rgba(255, 244, 252, 0.46);
      color: #2a1739;
      box-shadow: 0 16px 34px rgba(22, 12, 42, 0.16);
      backdrop-filter: blur(16px) saturate(1.06);
      cursor: pointer;
      transition: transform 160ms ease, background 160ms ease, border-color 160ms ease;
    }

    .self-button:active {
      transform: scale(0.96);
      background: rgba(255, 158, 227, 0.28);
      border-color: rgba(255, 231, 248, 0.54);
    }

    .boost-button {
      position: fixed;
      z-index: 35;
      right: max(16px, calc(env(safe-area-inset-right) + 16px));
      bottom: max(calc(var(--embed-bottom-offset) + 82px), calc(env(safe-area-inset-bottom) + var(--embed-bottom-offset) + 82px));
      width: 48px;
      height: 42px;
      padding: 0;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border: 1px solid rgba(255, 255, 255, 0.18);
      border-radius: 999px;
      background:
        radial-gradient(circle at 30% 24%, rgba(255, 255, 255, 0.22), transparent 34%),
        linear-gradient(135deg, rgba(255, 222, 143, 0.96), rgba(255, 159, 228, 0.94));
      color: #1d1630;
      font: 900 18px/1 var(--font);
      letter-spacing: 0;
      white-space: nowrap;
      box-shadow: 0 18px 38px rgba(0, 0, 0, 0.28);
      cursor: pointer;
      transition: transform 160ms ease, opacity 160ms ease, box-shadow 160ms ease;
    }

    .boost-button:active {
      transform: translateY(1px) scale(0.98);
    }

    .boost-button.is-boosting {
      transform: translateY(1px) scale(0.98);
      box-shadow:
        0 18px 40px rgba(255, 153, 228, 0.26),
        0 0 18px rgba(255, 236, 150, 0.38);
    }

    .boost-button:disabled {
      opacity: 0.72;
      cursor: default;
      box-shadow: 0 14px 30px rgba(0, 0, 0, 0.22);
    }

    .walk-action {
      position: fixed;
      z-index: 36;
      left: 50%;
      bottom: max(calc(var(--embed-primary-offset) + 18px), calc(env(safe-area-inset-bottom) + var(--embed-primary-offset) + 18px));
      min-width: min(306px, calc(100vw - 76px));
      max-width: calc(100vw - 56px);
      padding: 10px 20px 11px 12px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      border: 1px solid rgba(255, 255, 255, 0.22);
      border-radius: 999px;
      background:
        radial-gradient(circle at 18% 14%, rgba(255, 255, 255, 0.82), transparent 18%),
        radial-gradient(circle at 50% 0%, rgba(255, 255, 255, 0.28), transparent 56%),
        linear-gradient(135deg, rgba(255, 218, 132, 0.98), rgba(255, 143, 224, 0.96));
      color: #20162f;
      box-shadow:
        0 20px 42px rgba(0, 0, 0, 0.3),
        inset 0 1px 0 rgba(255, 255, 255, 0.42);
      transform: translateX(-50%);
      cursor: pointer;
      transition: transform 160ms ease, box-shadow 160ms ease, opacity 160ms ease;
    }

    .walk-action-icon {
      width: 38px;
      height: 38px;
      display: grid;
      place-items: center;
      flex: 0 0 auto;
      border: 2px solid rgba(80, 40, 92, 0.16);
      border-radius: 999px;
      background:
        linear-gradient(180deg, rgba(255, 255, 255, 0.88), rgba(255, 247, 217, 0.72)),
        radial-gradient(circle at 50% 0%, rgba(255, 255, 255, 0.92), rgba(255, 210, 122, 0.72));
      color: #8a3d90;
      font: 900 21px/1 var(--font);
      box-shadow:
        inset 0 1px 0 rgba(255, 255, 255, 0.78),
        0 7px 14px rgba(89, 42, 96, 0.18);
    }

    .walk-action-copy {
      min-width: 0;
      display: grid;
      gap: 3px;
      text-align: left;
    }

    .walk-action:active {
      transform: translateX(-50%) translateY(1px) scale(0.986);
    }

    .walk-action:disabled {
      cursor: default;
      opacity: 0.92;
      box-shadow: 0 16px 34px rgba(0, 0, 0, 0.26);
    }

    .walk-action strong {
      display: block;
      font: 900 16px/1 var(--font);
      letter-spacing: 0.01em;
      white-space: nowrap;
    }

    .walk-action-copy span {
      display: block;
      font: 700 11px/1 var(--font);
      color: rgba(32, 22, 47, 0.74);
      white-space: nowrap;
    }

    .self-button svg {
      width: 22px;
      height: 22px;
      fill: none;
      stroke: currentColor;
      stroke-linecap: round;
      stroke-linejoin: round;
      stroke-width: 2.4;
    }

    .rank-button {
      position: fixed;
      z-index: 34;
      right: max(16px, calc(env(safe-area-inset-right) + 16px));
      bottom: max(calc(var(--embed-bottom-offset) + 80px), calc(env(safe-area-inset-bottom) + var(--embed-bottom-offset) + 80px));
      width: 46px;
      height: 46px;
      display: grid;
      place-items: center;
      border: 1px solid rgba(255, 255, 255, 0.56);
      border-radius: 999px;
      background: rgba(255, 244, 252, 0.46);
      color: #2a1739;
      box-shadow: 0 16px 34px rgba(22, 12, 42, 0.16);
      backdrop-filter: blur(16px) saturate(1.06);
      cursor: pointer;
      transition: transform 160ms ease, background 160ms ease;
    }

    .rank-button:active {
      transform: scale(0.96);
      background: rgba(255, 222, 243, 0.72);
    }

    .rank-button svg {
      width: 22px;
      height: 22px;
      fill: none;
      stroke: currentColor;
      stroke-linecap: round;
      stroke-linejoin: round;
      stroke-width: 2.2;
    }

    .leaderboard-panel {
      position: fixed;
      z-index: 44;
      inset: 0;
      display: flex;
      align-items: flex-start;
      justify-content: center;
      padding:
        max(132px, calc(env(safe-area-inset-top) + 112px))
        max(14px, env(safe-area-inset-right))
        max(calc(var(--embed-bottom-offset) + 180px), calc(env(safe-area-inset-bottom) + var(--embed-bottom-offset) + 168px))
        max(14px, env(safe-area-inset-left));
      background: transparent;
      color: #231437;
    }

    .leaderboard-shell {
      position: relative;
      z-index: 0;
      width: min(842px, calc(100vw - 28px));
      max-height: min(1182px, calc(100dvh - var(--embed-bottom-offset) - env(safe-area-inset-top) - env(safe-area-inset-bottom) - 300px));
      margin-top: 0;
      display: grid;
      grid-template-rows: auto minmax(0, 1fr) auto;
      gap: 14px;
      padding: 18px;
      border: 1px solid rgba(255, 255, 255, 0.78);
      border-radius: 38px;
      background:
        radial-gradient(circle at 12% 4%, rgba(255, 255, 255, 0.82), rgba(255, 255, 255, 0) 22%),
        radial-gradient(circle at 92% 10%, rgba(255, 218, 174, 0.28), rgba(255, 218, 174, 0) 18%),
        linear-gradient(158deg, rgba(233, 245, 255, 0.98), rgba(246, 240, 255, 0.98) 42%, rgba(218, 209, 255, 0.96));
      box-shadow:
        0 30px 64px rgba(29, 16, 54, 0.3),
        inset 0 1px 0 rgba(255, 255, 255, 0.8);
      overflow: hidden;
      isolation: isolate;
      backdrop-filter: blur(12px) saturate(1.03);
    }

    .leaderboard-shell::before,
    .leaderboard-shell::after {
      content: "";
      position: absolute;
      inset: 0;
      pointer-events: none;
    }

    .leaderboard-shell::before {
      inset: -18px;
      z-index: -2;
      background:
        radial-gradient(circle at 20% 10%, rgba(255, 208, 230, 0.32), transparent 22%),
        radial-gradient(circle at 82% 84%, rgba(133, 237, 255, 0.28), transparent 22%),
        radial-gradient(ellipse at 50% 50%, rgba(255, 255, 255, 0.16), transparent 70%);
      filter: blur(14px);
      opacity: 0.92;
    }

    .leaderboard-shell::after {
      inset: 10px;
      z-index: -1;
      border-radius: 29px;
      border: 1px solid rgba(255, 255, 255, 0.56);
      opacity: 0.78;
    }

    .leaderboard-shell.is-step-list {
      width: min(470px, calc(100vw - 28px));
      max-height: min(620px, calc(100dvh - var(--embed-bottom-offset) - env(safe-area-inset-top) - env(safe-area-inset-bottom) - 330px));
      margin-top: min(44px, 5dvh);
    }

    .leaderboard-head {
      position: relative;
      z-index: 1;
      display: grid;
      grid-template-columns: auto minmax(0, 1fr);
      align-items: center;
      gap: 12px;
      padding: 4px 4px 0;
    }

    .leaderboard-head-icon {
      width: 46px;
      height: 46px;
      display: grid;
      place-items: center;
      border-radius: 999px;
      border: 2px solid rgba(191, 119, 17, 0.34);
      background:
        radial-gradient(circle at 34% 28%, rgba(255, 255, 255, 0.92), rgba(255, 249, 213, 0.84) 38%, rgba(255, 191, 88, 0.9) 64%, rgba(223, 138, 24, 0.96));
      color: #7f4600;
      font: 900 22px/1 var(--font);
      box-shadow:
        inset 0 1px 0 rgba(255, 255, 255, 0.74),
        0 12px 22px rgba(119, 59, 0, 0.18);
    }

    .leaderboard-head-icon.is-step {
      border-color: rgba(119, 95, 201, 0.3);
      background:
        radial-gradient(circle at 34% 28%, rgba(255, 255, 255, 0.94), rgba(255, 233, 247, 0.86) 34%, rgba(215, 191, 255, 0.92) 66%, rgba(139, 117, 246, 0.96));
      color: #57329b;
    }

    .leaderboard-head-copy {
      min-width: 0;
      display: grid;
      gap: 4px;
    }

    .leaderboard-title {
      margin: 0;
      font-size: 28px;
      font-weight: 900;
      line-height: 1.02;
      color: #4c315f;
    }

    .leaderboard-count {
      color: rgba(90, 66, 117, 0.74);
      font-size: 13px;
      font-weight: 800;
      white-space: nowrap;
    }

    .leaderboard-body {
      position: relative;
      z-index: 1;
      min-height: 0;
      display: grid;
      grid-template-rows: auto minmax(0, 1fr);
      gap: 12px;
      padding: 16px;
      border: 1.5px solid rgba(188, 152, 224, 0.84);
      border-radius: 28px;
      background:
        radial-gradient(circle at 50% 0%, rgba(255, 255, 255, 0.34), transparent 42%),
        linear-gradient(180deg, rgba(248, 243, 255, 0.98), rgba(239, 231, 252, 0.96));
      box-shadow:
        inset 0 1px 0 rgba(255, 255, 255, 0.8),
        0 12px 28px rgba(78, 47, 110, 0.08);
      overflow: hidden;
    }

    .leaderboard-shell.is-step-list .leaderboard-body {
      grid-template-rows: minmax(0, 1fr);
    }

    .leaderboard-tabs {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 8px;
      padding: 4px;
      border-radius: 999px;
      background: rgba(255, 255, 255, 0.5);
      box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.54);
    }

    .leaderboard-tab,
    .leaderboard-dismiss,
    .leaderboard-row {
      font: inherit;
    }

    .leaderboard-tab {
      height: 48px;
      border: 1px solid rgba(255, 255, 255, 0.62);
      border-radius: 999px;
      background: rgba(255, 255, 255, 0.24);
      color: rgba(72, 49, 96, 0.76);
      font-size: 15px;
      font-weight: 900;
      box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.38);
      cursor: pointer;
    }

    .leaderboard-tab.is-active {
      background: linear-gradient(135deg, rgba(255, 223, 151, 0.94), rgba(255, 170, 227, 0.94));
      color: #2a1739;
      border-color: rgba(255, 255, 255, 0.76);
      box-shadow:
        0 10px 20px rgba(255, 150, 212, 0.18),
        inset 0 1px 0 rgba(255, 255, 255, 0.58);
    }

    .leaderboard-list {
      overflow: auto;
      display: grid;
      align-content: start;
      gap: 10px;
      padding-right: 4px;
      overscroll-behavior: contain;
      min-height: 0;
    }

    .leaderboard-row {
      width: 100%;
      min-height: 66px;
      display: grid;
      grid-template-columns: 34px 40px minmax(0, 1fr) auto;
      align-items: center;
      gap: 11px;
      padding: 10px 12px;
      border: 1px solid rgba(255, 255, 255, 0.6);
      border-radius: 20px;
      background:
        radial-gradient(circle at 18% 16%, rgba(255, 255, 255, 0.42), transparent 22%),
        linear-gradient(135deg, rgba(255, 255, 255, 0.7), rgba(249, 243, 255, 0.88));
      color: #231437;
      text-align: left;
      box-shadow: 0 12px 24px rgba(38, 22, 67, 0.08);
      cursor: pointer;
    }

    .leaderboard-row.is-self {
      border-color: rgba(255, 202, 113, 0.98);
      background:
        radial-gradient(circle at 16% 18%, rgba(255, 255, 255, 0.82), transparent 28%),
        linear-gradient(135deg, rgba(255, 238, 187, 0.94), rgba(255, 204, 239, 0.88), rgba(213, 251, 255, 0.84));
      box-shadow:
        0 12px 26px rgba(92, 48, 129, 0.13),
        inset 0 0 0 1px rgba(255, 255, 255, 0.52);
    }

    .leaderboard-row.is-self .leaderboard-rank,
    .leaderboard-row.is-self .leaderboard-score {
      color: #743d07;
    }

    .leaderboard-divider {
      display: grid;
      grid-template-columns: 1fr auto 1fr;
      align-items: center;
      gap: 10px;
      padding: 2px 6px;
      color: rgba(93, 66, 121, 0.62);
      font-size: 11px;
      font-weight: 800;
    }

    .leaderboard-divider::before,
    .leaderboard-divider::after {
      content: "";
      height: 1px;
      background: rgba(93, 66, 121, 0.18);
    }

    .leaderboard-rank {
      color: rgba(78, 56, 103, 0.78);
      font-size: 13px;
      font-weight: 800;
      text-align: center;
    }

    .leaderboard-avatar {
      width: 40px;
      height: 40px;
      overflow: hidden;
      border-radius: 999px;
      border: 1px solid rgba(255, 255, 255, 0.72);
      background: rgba(255, 184, 233, 0.18);
      display: grid;
      place-items: center;
      font-size: 13px;
      font-weight: 700;
    }

    .leaderboard-avatar img {
      width: 100%;
      height: 100%;
      display: block;
      object-fit: cover;
    }

    .leaderboard-name {
      min-width: 0;
      display: grid;
      gap: 2px;
    }

    .leaderboard-name strong,
    .leaderboard-name small {
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .leaderboard-name strong {
      font-size: 15px;
      line-height: 1.1;
    }

    .leaderboard-name small {
      color: rgba(90, 66, 117, 0.74);
      font-size: 11px;
      font-weight: 800;
    }

    .leaderboard-score {
      color: #8f4e0f;
      font-size: 16px;
      font-weight: 900;
      white-space: nowrap;
    }

    .leaderboard-footer {
      position: relative;
      z-index: 1;
      display: flex;
      justify-content: center;
      padding: 0 4px 2px;
    }

    .leaderboard-dismiss {
      width: min(320px, 100%);
      min-height: 54px;
      border: 1px solid rgba(255, 255, 255, 0.68);
      border-radius: 999px;
      background:
        radial-gradient(circle at 20% 20%, rgba(255, 255, 255, 0.78), transparent 26%),
        linear-gradient(135deg, rgba(255, 221, 151, 0.96), rgba(255, 164, 224, 0.94));
      color: #261536;
      font-weight: 800;
      font-size: 16px;
      letter-spacing: 0.01em;
      box-shadow: 0 14px 28px rgba(44, 23, 76, 0.12);
      cursor: pointer;
    }

    .leaderboard-empty {
      min-height: 138px;
      display: grid;
      place-items: center;
      padding: 18px 14px;
      border: 1px solid rgba(255, 255, 255, 0.56);
      border-radius: 18px;
      background: rgba(255, 255, 255, 0.42);
      color: rgba(86, 61, 112, 0.76);
      font-size: 13px;
      font-weight: 800;
      text-align: center;
      box-shadow: 0 8px 22px rgba(38, 22, 67, 0.08);
    }

    .mileage-login-button {
      position: fixed;
      z-index: 45;
      left: 50%;
      bottom: max(calc(var(--embed-bottom-offset) + 18px), calc(env(safe-area-inset-bottom) + var(--embed-bottom-offset) + 18px));
      transform: translateX(-50%);
      min-width: 154px;
      height: 38px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      padding: 0 14px;
      border: 1px solid rgba(245, 251, 255, 0.18);
      border-radius: 999px;
      background: rgba(88, 101, 242, 0.82);
      color: #ffffff;
      font: 700 12px/1 var(--font);
      text-decoration: none;
      box-shadow: 0 14px 34px rgba(0, 0, 0, 0.28);
      backdrop-filter: blur(12px);
    }

    .zoom-slider-wrap {
      position: fixed;
      z-index: 32;
      right: max(18px, calc(env(safe-area-inset-right) + 18px));
      top: 50%;
      width: 36px;
      height: min(300px, 42vh);
      transform: translateY(-50%);
      display: none;
      align-items: center;
      justify-content: center;
      border: 1px solid rgba(141, 242, 255, 0.14);
      border-radius: 999px;
      background: rgba(8, 13, 31, 0.58);
      box-shadow: 0 18px 42px rgba(0, 0, 0, 0.28);
      backdrop-filter: blur(14px);
    }

    .zoom-slider {
      width: min(260px, 36vh);
      height: 32px;
      transform: rotate(-90deg);
      accent-color: #8df2ff;
      cursor: grab;
    }

    .zoom-slider:active {
      cursor: grabbing;
    }

    .toast {
      position: fixed;
      left: 50%;
      bottom: max(calc(var(--embed-bottom-offset) + 18px), calc(env(safe-area-inset-bottom) + var(--embed-bottom-offset) + 18px));
      z-index: 42;
      max-width: calc(100vw - 92px);
      transform: translateX(-50%);
      padding: 11px 14px;
      border: 1px solid rgba(141, 242, 255, 0.16);
      border-radius: 999px;
      background: rgba(14, 24, 58, 0.96);
      color: var(--ink);
      font-size: 12px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      box-shadow: 0 18px 42px rgba(0, 0, 0, 0.3);
      pointer-events: none;
    }

    .pickup-layer {
      position: fixed;
      inset: 0;
      z-index: 62;
      pointer-events: none;
      overflow: hidden;
      contain: strict;
    }

    .pickup-coin {
      position: absolute;
      left: 0;
      top: 0;
      width: var(--pickup-size, 38px);
      height: var(--pickup-size, 38px);
      display: grid;
      place-items: center;
      opacity: 0;
      transform: translate3d(var(--from-x, 0px), var(--from-y, 0px), 0) scale(0.5);
      animation: mileagePickupFly var(--pickup-duration, 1180ms) cubic-bezier(0.16, 0.9, 0.18, 1) var(--pickup-delay, 0ms) both;
      will-change: transform, opacity, filter;
    }

    .pickup-coin::before {
      content: "";
      position: absolute;
      inset: -30%;
      border-radius: 999px;
      background:
        radial-gradient(circle, rgba(255, 249, 190, 0.66), rgba(142, 241, 255, 0.2) 42%, transparent 68%);
      opacity: 0;
      animation: mileagePickupGlow var(--pickup-duration, 1180ms) ease var(--pickup-delay, 0ms) both;
    }

    .pickup-coin img,
    .pickup-coin span {
      position: relative;
      z-index: 1;
      width: 100%;
      height: 100%;
      object-fit: contain;
      border-radius: 999px;
      filter:
        drop-shadow(0 8px 10px rgba(0, 0, 0, 0.36))
        drop-shadow(0 0 10px rgba(255, 236, 152, 0.5));
      animation: mileagePickupSpin var(--pickup-duration, 1180ms) cubic-bezier(0.18, 0.86, 0.22, 1) var(--pickup-delay, 0ms) both;
    }

    .pickup-coin span {
      display: grid;
      place-items: center;
      border: 2px solid rgba(255, 247, 197, 0.86);
      background:
        radial-gradient(circle at 34% 24%, rgba(255, 255, 255, 0.56), transparent 28%),
        linear-gradient(180deg, #ffe7a8, #ffb83e 58%, #d27617);
      color: #5b3100;
      font: 800 calc(var(--pickup-size, 38px) * 0.46)/1 var(--font);
    }

    @keyframes selfPulse {
      0%,
      100% {
        filter: drop-shadow(0 0 0 rgba(255, 142, 219, 0));
      }

      50% {
        filter: drop-shadow(0 0 9px rgba(255, 142, 219, 0.78));
      }
    }

    @keyframes rewardClaimPop {
      0% {
        transform: translate(-50%, -50%) scale(0.82);
        opacity: 0.18;
      }

      38% {
        transform: translate(-50%, -50%) scale(1.16);
        opacity: 1;
      }

      100% {
        transform: translate(-50%, -50%) scale(1);
        opacity: 0.58;
      }
    }

    @keyframes mileagePickupFly {
      0% {
        opacity: 0;
        transform: translate3d(var(--from-x), var(--from-y), 0) scale(0.46);
        filter: blur(1.4px);
      }

      10% {
        opacity: 1;
        transform: translate3d(var(--burst-x), var(--burst-y), 0) scale(1.16);
        filter: blur(0);
      }

      34% {
        opacity: 1;
        transform: translate3d(var(--float-x), var(--float-y), 0) scale(1.08);
      }

      62% {
        opacity: 1;
        transform: translate3d(var(--mid-x), var(--mid-y), 0) scale(0.92);
      }

      100% {
        opacity: 0;
        transform: translate3d(var(--to-x), var(--to-y), 0) scale(0.22);
        filter: blur(0.7px);
      }
    }

    @keyframes mileagePickupGlow {
      0%,
      100% {
        opacity: 0;
        transform: scale(0.58);
      }

      12% {
        opacity: 0.9;
        transform: scale(1.08);
      }

      42% {
        opacity: 0.56;
        transform: scale(0.94);
      }
    }

    @keyframes mileagePickupSpin {
      0% {
        transform: rotate(calc(var(--pickup-rotate-start, -18) * 1deg)) scale(0.88);
      }

      36% {
        transform: rotate(calc(var(--pickup-rotate-mid, 14) * 1deg)) scale(1.1);
      }

      100% {
        transform: rotate(calc(var(--pickup-rotate-end, 40) * 1deg)) scale(0.72);
      }
    }

    @media (hover: hover) and (pointer: fine) {
      .zoom-slider-wrap {
        display: flex;
      }
    }

    @media (max-width: 560px) {
      .zoom-slider-wrap {
        display: none;
      }

      .hud-card {
        width: auto;
        max-width: calc(100vw - 72px);
        padding: 0;
        border-radius: 0;
      }

      .hud-card h1 {
        font-size: 14px;
      }

      .hud-card p {
        font-size: 11px;
      }

      .summary-strip {
        gap: 5px;
        margin-top: 7px;
      }

      .pill {
        padding: 4px 6px;
        font-size: 9px;
      }

      .self-button {
        width: 48px;
        height: 48px;
        border-radius: 999px;
      }

      .rank-button {
        width: 44px;
        height: 44px;
        bottom: max(calc(var(--embed-bottom-offset) + 78px), calc(env(safe-area-inset-bottom) + var(--embed-bottom-offset) + 78px));
      }

      .leaderboard-shell {
        width: min(100%, calc(100vw - 20px));
        max-height: calc(100dvh - var(--embed-bottom-offset) - env(safe-area-inset-top) - env(safe-area-inset-bottom) - 300px);
        margin-top: 0;
        padding: 14px;
        gap: 10px;
        border-radius: 30px;
      }

      .leaderboard-shell.is-step-list {
        width: min(100%, calc(100vw - 28px));
        max-height: calc(100dvh - var(--embed-bottom-offset) - env(safe-area-inset-top) - env(safe-area-inset-bottom) - 330px);
        margin-top: min(44px, 5dvh);
      }

      .leaderboard-head-icon {
        width: 42px;
        height: 42px;
        font-size: 20px;
      }

      .leaderboard-title {
        font-size: 23px;
      }

      .leaderboard-body {
        padding: 12px;
        border-radius: 23px;
      }

      .leaderboard-row {
        min-height: 60px;
        grid-template-columns: 32px 38px minmax(0, 1fr) auto;
        gap: 8px;
        padding: 10px;
      }

      .boost-button {
        right: max(12px, calc(env(safe-area-inset-right) + 12px));
        bottom: max(calc(var(--embed-bottom-offset) + 78px), calc(env(safe-area-inset-bottom) + var(--embed-bottom-offset) + 78px));
        height: 40px;
        padding: 0 14px;
        font-size: 12px;
      }

      .toast {
        max-width: calc(100vw - 84px);
      }
    }

    @media (max-width: 560px) and (hover: hover) and (pointer: fine) {
      .zoom-slider-wrap {
        display: flex;
      }
    }
  </style>
</head>
<body class="<?php echo $previewMode ? 'is-preview-mode' : ''; ?>">
  <div id="viewer" class="viewer">
    <div id="scene" class="scene" aria-label="Mileage board viewer">
      <div id="boardShell" class="board-shell is-hidden" aria-label="Mileage board">
        <div id="boardTrack" class="board-track"></div>
        <canvas id="boardCanvas" class="board-canvas" aria-hidden="true"></canvas>
        <div class="board-overlay" aria-hidden="true">
          <div id="stepLayer" class="overlay-layer"></div>
          <div id="rewardLayer" class="overlay-layer"></div>
          <div id="spriteLayer" class="overlay-layer"></div>
          <div id="playerLayer" class="overlay-layer"></div>
          <div id="selfLayer" class="overlay-layer"></div>
        </div>
      </div>
    </div>

    <section id="hudCard" class="hud-card">
      <h1>กิจกรรม Mileage</h1>
      <p id="subtitle">กำลังโหลดตำแหน่งของคุณ</p>
      <div id="summaryStrip" class="summary-strip"></div>
    </section>

    <div id="zoomSliderWrap" class="zoom-slider-wrap" aria-hidden="true">
      <input id="zoomSlider" class="zoom-slider" type="range" min="0" max="1000" value="500" />
    </div>

    <button id="selfButton" class="self-button" type="button" aria-label="ไปที่ตัวฉัน">
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="M12 5v14" />
        <path d="M5 12h14" />
        <path d="M16.5 7.5a6.4 6.4 0 0 0-9 9" />
        <path d="M7.5 16.5a6.4 6.4 0 0 0 9-9" />
      </svg>
    </button>

    <button id="rankButton" class="rank-button" type="button" aria-label="ดู Leaderboard">
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="M8 21V10" />
        <path d="M16 21V6" />
        <path d="M4 21h16" />
        <path d="M6 10h4" />
        <path d="M14 6h4" />
        <path d="M11 3h2l.7 1.4 1.5.2-1.1 1.1.3 1.5L12 6.5 9.6 7.2l.3-1.5-1.1-1.1 1.5-.2L11 3Z" />
      </svg>
    </button>

    <section id="leaderboardPanel" class="leaderboard-panel is-hidden" aria-hidden="true">
      <div class="leaderboard-shell" role="dialog" aria-modal="true" aria-labelledby="leaderboardTitle">
        <div class="leaderboard-head">
          <span class="leaderboard-head-icon" aria-hidden="true">P</span>
          <div class="leaderboard-head-copy">
            <h2 id="leaderboardTitle" class="leaderboard-title">Leaderboard</h2>
            <div id="leaderboardCount" class="leaderboard-count">0 คน</div>
          </div>
        </div>
        <div class="leaderboard-body">
          <div class="leaderboard-tabs" role="tablist" aria-label="เลือกอันดับ mileage">
            <button class="leaderboard-tab is-active" type="button" data-rank-tab="all">ทั้งหมด</button>
            <button class="leaderboard-tab" type="button" data-rank-tab="weekly">สัปดาห์</button>
          </div>
          <div id="leaderboardList" class="leaderboard-list"></div>
        </div>
        <div class="leaderboard-footer">
          <button id="leaderboardClose" class="leaderboard-dismiss" type="button" aria-label="ปิด">ปิด</button>
        </div>
      </div>
    </section>

    <section id="stepPlayersPanel" class="leaderboard-panel is-hidden" aria-hidden="true">
      <div class="leaderboard-shell is-step-list" role="dialog" aria-modal="true" aria-labelledby="stepPlayersTitle">
        <div class="leaderboard-head">
          <span class="leaderboard-head-icon is-step" aria-hidden="true">#</span>
          <div class="leaderboard-head-copy">
            <h2 id="stepPlayersTitle" class="leaderboard-title">ผู้เล่นในช่อง</h2>
            <div id="stepPlayersCount" class="leaderboard-count">0 คน</div>
          </div>
        </div>
        <div class="leaderboard-body">
          <div id="stepPlayersList" class="leaderboard-list"></div>
        </div>
        <div class="leaderboard-footer">
          <button id="stepPlayersClose" class="leaderboard-dismiss" type="button" aria-label="ปิด">ปิด</button>
        </div>
      </div>
    </section>

    <button id="boostButton" class="boost-button is-hidden" type="button" aria-label="กดค้างเพื่อเร่งความเร็วแอนิเมชัน">
      &gt;&gt;
    </button>

    <button id="walkActionButton" class="walk-action is-hidden" type="button" aria-label="เริ่มเดิน Mileage">
      <span class="walk-action-icon" aria-hidden="true">›</span>
      <span class="walk-action-copy">
        <strong id="walkActionTitle">เริ่มเดิน</strong>
        <span id="walkActionCount">เหลือ 0 ก้าว</span>
      </span>
    </button>

    <section id="messageCard" class="message-card is-hidden">
      <h2 id="messageTitle">กำลังโหลด</h2>
      <p id="messageBody"></p>
    </section>

    <a id="mileageLoginButton" class="mileage-login-button is-hidden" href="./api/auth/bridge.php?flow=gacha" rel="external noopener">Sign in Discord</a>

    <div id="pickupLayer" class="pickup-layer" aria-hidden="true"></div>
    <div id="toast" class="toast is-hidden"></div>
  </div>

  <script>
    window.ASSET_MANIFEST_RUNTIME_BOOT = { apiUrl: "asset-manifest-api.php" };
  </script>
  <script src="assets/js/asset-manifest-runtime.js?v=<?php echo htmlspecialchars($assetManifestRuntimeVersion, ENT_QUOTES, 'UTF-8'); ?>"></script>
  <script>
    const initialBoardCode = <?php echo json_encode($boardCode, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    const initialPlayerToken = <?php echo json_encode($playerToken, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    const initialPreviewMode = <?php echo $previewMode ? 'true' : 'false'; ?>;

    const viewer = document.getElementById("viewer");
    const scene = document.getElementById("scene");
    const boardShell = document.getElementById("boardShell");
    const boardTrack = document.getElementById("boardTrack");
    const boardCanvas = document.getElementById("boardCanvas");
    const boardCtx = boardCanvas.getContext("2d");
    const stepLayer = document.getElementById("stepLayer");
    const rewardLayer = document.getElementById("rewardLayer");
    const spriteLayer = document.getElementById("spriteLayer");
    const playerLayer = document.getElementById("playerLayer");
    const selfLayer = document.getElementById("selfLayer");
    const subtitle = document.getElementById("subtitle");
    const summaryStrip = document.getElementById("summaryStrip");
    const messageCard = document.getElementById("messageCard");
    const messageTitle = document.getElementById("messageTitle");
    const messageBody = document.getElementById("messageBody");
    const toast = document.getElementById("toast");
    const selfButton = document.getElementById("selfButton");
    const rankButton = document.getElementById("rankButton");
    const leaderboardPanel = document.getElementById("leaderboardPanel");
    const leaderboardTitle = document.getElementById("leaderboardTitle");
    const leaderboardCount = document.getElementById("leaderboardCount");
    const leaderboardList = document.getElementById("leaderboardList");
    const leaderboardClose = document.getElementById("leaderboardClose");
    const stepPlayersPanel = document.getElementById("stepPlayersPanel");
    const stepPlayersTitle = document.getElementById("stepPlayersTitle");
    const stepPlayersCount = document.getElementById("stepPlayersCount");
    const stepPlayersList = document.getElementById("stepPlayersList");
    const stepPlayersClose = document.getElementById("stepPlayersClose");
    const mileageLoginButton = document.getElementById("mileageLoginButton");
    const boostButton = document.getElementById("boostButton");
    const walkActionButton = document.getElementById("walkActionButton");
    const walkActionTitle = document.getElementById("walkActionTitle");
    const walkActionCount = document.getElementById("walkActionCount");
    const pickupLayer = document.getElementById("pickupLayer");
    const zoomSlider = document.getElementById("zoomSlider");

    const apiUrl = new URL("mileage-api.php", window.location.href);
    const markerStyle = {
      rewardSize: 44,
      otherSize: 30,
      selfSize: 52,
      otherLift: 10,
      selfLift: 35,
      clusterOffset: 18,
      clusterLift: 22,
      stepBadgeSize: 24,
      stepBadgeOffset: 18
    };
    const rewardPickupIcons = {
      coin: "images/icon_coin.png",
      gem: "images/icon_gem.png",
      ticket: "images/icon_ticket.png",
      potion: "images/icon_gelato.png"
    };
    const boardDecorBlueprints = {
      main: [
        {
          id: "spaceRocket",
          assetSrc: "images/pic_element_mileage_1.png",
          rect: {
            x: 609,
            y: 2436,
            width: 165,
            height: 328
          },
          parallax: {
            factorX: 0.0075,
            factorY: 0.0105,
            maxX: 8,
            maxY: 18
          },
          idle: {
            restLift: 14,
            swayX: 1.8,
            bobY: 5.8,
            swayMs: 5200,
            bobMs: 3600,
            phase: 0.18
          }
        }
      ]
    };

    function debugFlag(value, fallback = false) {
      if (value === null) return fallback;
      const normalized = String(value).trim().toLowerCase();
      if (normalized === "") return fallback;
      return ["1", "true", "yes", "on"].includes(normalized);
    }

    function debugNumber(value, fallback, min = -Infinity, max = Infinity) {
      const parsed = Number(value);
      if (!Number.isFinite(parsed)) return fallback;
      return clamp(parsed, min, max);
    }

    function readDebugConfig() {
      const params = new URLSearchParams(window.location.search);
      return {
        enabled: Array.from(params.keys()).some((key) => /^debug_/i.test(key)),
        walk: debugFlag(params.get("debug_walk")),
        start: Math.trunc(debugNumber(params.get("debug_start"), -1, -1, 100000)),
        steps: Math.trunc(debugNumber(params.get("debug_steps"), 8, 0, 100000)),
        friends: Math.trunc(debugNumber(params.get("debug_friends"), 0, 0, 300)),
        cityZone: String(params.get("debug_city_zone") || "").trim().toLowerCase(),
        autoplay: debugFlag(params.get("debug_autoplay"), false),
      };
    }

	    const debugConfig = readDebugConfig();
	    const DEFAULT_UPLOADED_SPRITE_EDGE_FADE = 3;

    const state = {
      bootstrap: null,
      board: null,
      boardReady: false,
      preview: {
        enabled: initialPreviewMode,
        simulation: {},
        bootstrapped: false
      },
      debug: debugConfig,
      camera: {
        x: 0,
        y: 0,
        scale: 1,
        minScale: 0.2,
        maxScale: 3,
        detailScale: 1,
        walkScale: 1
      },
      cameraMotion: {
        active: false,
        vx: 0,
        vy: 0,
        lastAt: 0,
        followPathX: false,
        lockPathY: false,
        followStrength: 0,
        verticalStrength: 0,
        alignY: 0.58,
        snapToStep: false
      },
      cameraTween: {
        active: false,
        startAt: 0,
        duration: 0,
        fromX: 0,
        fromY: 0,
        fromScale: 1,
        toX: 0,
        toY: 0,
        toScale: 1
      },
      gesture: {
        pointers: new Map(),
        dragStart: null,
        pinchStart: null,
        lastMoveX: 0,
        lastMoveY: 0,
        lastMoveAt: 0,
        velocityX: 0,
        velocityY: 0,
        assistStrength: 0,
        pathLock: false,
        manualOverrideUntil: 0
      },
      leaderboardTab: "all",
      leaderboardOpen: false,
      stepPlayersPanel: {
        open: false,
        stepIndex: -1,
        loading: false,
        players: []
      },
      walk: {
        active: false,
        startAt: 0,
        currentValue: -1,
        fromValue: -1,
        toValue: -1,
        segmentFromValue: -1,
        segmentToValue: -1,
        segmentStartAt: 0,
        segmentDurationMs: 0,
        visualLiftPx: markerStyle.selfLift,
        visualScaleX: 1,
        visualScaleY: 1,
        visualTiltDeg: 0,
        visualOffsetX: 0,
        visualOffsetY: 0,
        boost: false,
        pendingClaim: false,
        restoreScale: 1
      },
      selfFx: {
        particles: [],
        activeStepGlow: null,
        landingPulse: null,
        lastPoint: null,
        lastUpdateAt: 0,
        lastEmitAt: 0,
        velocityX: 0,
        velocityY: 0,
        seed: 0
      },
      ambience: {
        seed: 914271,
        cloudSeed: 37511,
        lastCameraX: 0,
        lastCameraY: 0,
        lastCameraAt: 0,
        cameraVelocityX: 0,
        cameraVelocityY: 0
      },
      boardDecor: {
        layers: [],
        preparedKey: ""
      },
      claimedRewardIds: new Set(),
      recentClaimedRewardIds: new Set(),
      recentClaimRewardTimer: 0,
      toastTimer: 0,
      animationFrame: 0,
      time: {
        pauseStartedAt: 0,
        pausedDuration: 0
      },
      renderIndex: {
        stepGroupsBySegment: new Map(),
        rewardsBySegment: new Map(),
        spritesBySegment: new Map(),
        rewardStepIndexes: new Set()
      },
	      canvas: {
	        imageCache: new Map(),
	        softSpriteCache: new Map(),
	        dpr: 1,
        viewportWidth: 0,
        viewportHeight: 0,
        lastVisibleRect: null,
        playerClusterTargets: [],
        lastVisibleCounts: {
          segments: 0,
          steps: 0,
          rewards: 0,
          sprites: 0,
          players: 0
        }
      }
    };

    if (window.parent && window.parent !== window) {
      document.documentElement.style.setProperty("--embed-bottom-offset", "92px");
      document.documentElement.style.setProperty("--embed-primary-offset", "128px");
    }

    function escapeHtml(value) {
      return String(value ?? "")
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll("\"", "&quot;");
    }

    function escapeCssUrl(value) {
      return escapeHtml(value).replaceAll("'", "%27").replaceAll(")", "%29");
    }

    function clamp(value, min, max) {
      return Math.max(min, Math.min(max, value));
    }

	    function plainObject(value) {
	      return value && typeof value === "object" && !Array.isArray(value);
	    }

	    function isUploadedSpritePath(path) {
	      const value = String(path || "").trim().toLowerCase();
	      return value.startsWith("uploads/") || value.startsWith("images/uploads/");
	    }

    function lerp(start, end, t) {
      return start + (end - start) * t;
    }

    function cloneJson(value) {
      return JSON.parse(JSON.stringify(value));
    }

    function animationNow() {
      return performance.now() - state.time.pausedDuration;
    }

    function boardUiSettings() {
      const meta = plainObject(state.board?.meta) ? state.board.meta : {};
      const ui = plainObject(meta.ui) ? meta.ui : {};
      const rewardMarker = plainObject(ui.rewardMarker) ? ui.rewardMarker : {};
      const currencyPickup = plainObject(ui.currencyPickup) ? ui.currencyPickup : {};
      return {
        rewardMarker: {
          size: clamp(Number(rewardMarker.size || markerStyle.rewardSize), 26, 96)
        },
        currencyPickup: {
          scale: clamp(Number(currencyPickup.scale || 1.28), 0.7, 2.4),
          countMultiplier: clamp(Number(currencyPickup.countMultiplier || 1.4), 0.7, 3.2)
        }
      };
    }

    function applyBoardUiSettings() {
      const ui = boardUiSettings();
      markerStyle.rewardSize = Math.round(ui.rewardMarker.size);
    }

    function easeInOutCubic(t) {
      const x = clamp(t, 0, 1);
      return x < 0.5 ? 4 * x * x * x : 1 - Math.pow(-2 * x + 2, 3) / 2;
    }

    function easeOutCubic(t) {
      const x = clamp(t, 0, 1);
      return 1 - Math.pow(1 - x, 3);
    }

    function easeOutQuart(t) {
      const x = clamp(t, 0, 1);
      return 1 - Math.pow(1 - x, 4);
    }

    function easeInOutSine(t) {
      const x = clamp(t, 0, 1);
      return -(Math.cos(Math.PI * x) - 1) / 2;
    }

    function seededNoise() {
      state.selfFx.seed = (state.selfFx.seed + 0x6D2B79F5) >>> 0;
      let t = state.selfFx.seed;
      t = Math.imul(t ^ (t >>> 15), t | 1);
      t ^= t + Math.imul(t ^ (t >>> 7), t | 61);
      return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
    }

    function hashNoise(seed) {
      let t = (Number(seed) || 0) >>> 0;
      t += 0x6D2B79F5;
      t = Math.imul(t ^ (t >>> 15), t | 1);
      t ^= t + Math.imul(t ^ (t >>> 7), t | 61);
      return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
    }

    function selfParticleToneColor(tone, kind = "trail") {
      const value = clamp(Number(tone || 0), 0, 1);
      if (kind === "burst") {
        if (value < 0.24) return "255, 222, 132";
        if (value < 0.46) return "126, 236, 255";
        if (value < 0.64) return "255, 146, 222";
        if (value < 0.82) return "154, 255, 207";
        return "194, 150, 255";
      }
      if (value < 0.36) return "255, 205, 116";
      if (value < 0.68) return "126, 236, 255";
      return "255, 132, 223";
    }

    function isTouchLikePointer(event) {
      const pointerType = String(event?.pointerType || "");
      return pointerType === "touch" || pointerType === "pen";
    }

    function showMessage(title, body) {
      messageTitle.textContent = title;
      messageBody.textContent = body;
      messageCard.classList.remove("is-hidden");
    }

    function hideMessage() {
      messageCard.classList.add("is-hidden");
    }

    function syncLoginButton(visible) {
      if (!mileageLoginButton) return;
      if (state.preview.enabled) {
        mileageLoginButton.classList.add("is-hidden");
        mileageLoginButton.setAttribute("aria-hidden", "true");
        return;
      }
      const loginUrl = new URL("./api/auth/bridge.php", window.location.href);
      loginUrl.searchParams.set("flow", "gacha");
      loginUrl.searchParams.set("return_to", window.location.href);
      mileageLoginButton.href = loginUrl.toString();
      mileageLoginButton.classList.toggle("is-hidden", !visible);
      mileageLoginButton.setAttribute("aria-hidden", visible ? "false" : "true");
    }

    function showToast(text, durationMs = 2200) {
      toast.textContent = text;
      toast.classList.remove("is-hidden");
      if (state.toastTimer) {
        window.clearTimeout(state.toastTimer);
      }
      state.toastTimer = window.setTimeout(() => {
        toast.classList.add("is-hidden");
        state.toastTimer = 0;
      }, durationMs);
    }

    function createAuthHeaders(base = null) {
      const headers = new Headers(base || {});
      if (initialPlayerToken) {
        headers.set("X-Gacha-Player-Token", initialPlayerToken);
      }
      return headers;
    }

    async function fetchMileageJson(action, body = null) {
      if (state.preview.enabled) {
        if (action === "bootstrap") {
          action = "preview_bootstrap";
        } else if (action === "claim_pending") {
          return simulatedPreviewClaim();
        } else if (action === "leaderboard") {
          return { ok: true, leaderboard: { all: [], weekly: [] } };
        } else if (action === "step_players") {
          return { ok: true, players: previewPlayersForStep(Number(body?.stepIndex ?? -1)) };
        }
      }
      const url = new URL(apiUrl.toString());
      url.searchParams.set("action", action);
      url.searchParams.set("boardCode", initialBoardCode);
      if (initialPlayerToken) {
        url.searchParams.set("player_token", initialPlayerToken);
      }
      const response = await fetch(url, {
        method: body ? "POST" : "GET",
        cache: "no-store",
        credentials: "same-origin",
        headers: createAuthHeaders(body ? { "Content-Type": "application/json" } : null),
        body: body ? JSON.stringify(body) : null
      });
      const data = await response.json().catch(() => null);
      if (!response.ok || !data || data.ok === false) {
        const error = new Error(data?.message || data?.code || `HTTP ${response.status}`);
        error.data = data || {};
        throw error;
      }
      return data;
    }

    function sceneRect() {
      return scene.getBoundingClientRect();
    }

    function boardSize() {
      return {
        width: Math.max(1, Number(state.board?.image?.width || 1)),
        height: Math.max(1, Number(state.board?.image?.height || 1))
      };
    }

    function boardSegments() {
      const segments = Array.isArray(state.board?.image?.segments) ? state.board.image.segments : [];
      if (segments.length > 0) {
        return segments;
      }
      const source = String(state.board?.image?.source || "").trim();
      if (source) {
        return [{
          id: "segment_001",
          src: source,
          y: 0,
          h: state.board?.image?.height || 0
        }];
      }
      return [];
    }

    function activeBoardDecorBlueprints() {
      const boardCode = String(state.board?.boardCode || initialBoardCode || "").trim().toLowerCase();
      if (state.boardDecor.preparedKey !== boardCode) {
        state.boardDecor.preparedKey = boardCode;
        state.boardDecor.layers = Array.isArray(boardDecorBlueprints[boardCode]) ? boardDecorBlueprints[boardCode] : [];
      }
      return Array.isArray(state.boardDecor.layers) ? state.boardDecor.layers : [];
    }

    // MILEAGE_SEGMENT_MODEL_V2: board positions resolve from segment-local points first,
    // then expose derived global x/y for rendering compatibility.
    function boardSegmentById(segmentId) {
      const normalized = String(segmentId || "").trim();
      return boardSegments().find((segment) => String(segment?.id || "") === normalized) || boardSegments()[0] || null;
    }

    function boardPointFromLocation(location) {
      if (!state.board || !location) return null;
      const size = boardSize();
      if (
        typeof location.localX === "number"
        && typeof location.localY === "number"
        && location.segmentId
      ) {
        const segment = boardSegmentById(location.segmentId);
        if (segment) {
          return {
            x: clamp(location.localX, 0, 1) * size.width,
            y: Number(segment.y || 0) + (clamp(location.localY, 0, 1) * Math.max(1, Number(segment.h || 1)))
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

    function boardStep(index) {
      if (!state.board) return null;
      return state.board.steps?.[index] || null;
    }

    function boardPointFromStep(index) {
      return boardPointFromLocation(boardStep(index));
    }

    function boardEntryPoint() {
      if (!state.board) return null;
      const entry = state.board.entry || state.board.steps?.[0] || null;
      return boardPointFromLocation(entry) || boardPointFromLocation({ x: 0.4123, y: 0.9721 });
    }

    function pointForPlayer(player) {
      if (!player) return null;
      if (typeof player.positionStep === "number" && player.positionStep >= 0) {
        return boardPointFromStep(player.positionStep);
      }
      return boardEntryPoint();
    }

    function rewardPoint(reward) {
      if (!state.board || !reward) return null;
      const directPoint = boardPointFromLocation(reward);
      if (directPoint) return directPoint;
      if (Number.isInteger(reward.stepIndex)) {
        return boardPointFromStep(reward.stepIndex);
      }
      return null;
    }

    function spritePoint(sprite) {
      return boardPointFromLocation(sprite);
    }

    function iconTemplateById(id) {
      const target = String(id || "").trim();
      if (target === "") return null;
      return (Array.isArray(state.board?.iconTemplates) ? state.board.iconTemplates : [])
        .find((template) => String(template?.id || "") === target) || null;
    }

    function rewardNodeDrawable(node) {
      const meta = node && typeof node.meta === "object" && !Array.isArray(node.meta) ? node.meta : {};
      return {
        ...node,
        kind: String(node?.kind || meta.kind || "coin"),
        amount: Math.max(1, Number(node?.amount || meta.amount || 1)),
        itemCode: String(node?.itemCode || meta.itemCode || ""),
        __rewardNode: true
      };
    }

    function pathPointForValue(stepValue) {
      if (!state.board) return null;
      if (!state.board.steps?.length) return boardEntryPoint();
      const maxIndex = state.board.steps.length - 1;
      const clampedValue = clamp(stepValue, -1, maxIndex);
      if (clampedValue < 0) {
        return boardPointFromStep(0) || boardEntryPoint();
      }
      const low = Math.floor(clampedValue);
      const high = Math.min(maxIndex, Math.ceil(clampedValue));
      const t = clampedValue - low;
      const lowPoint = boardPointFromStep(low);
      const highPoint = boardPointFromStep(high) || lowPoint;
      if (!lowPoint || !highPoint) return null;
      return {
        x: lerp(lowPoint.x, highPoint.x, t),
        y: lerp(lowPoint.y, highPoint.y, t)
      };
    }

    function currentSelfPoint() {
      if (state.walk.active) {
        return pathPointForValue(state.walk.currentValue) || boardEntryPoint();
      }
      const summary = state.bootstrap?.summary || {};
      const pendingSteps = Number(summary.pendingSteps || 0);
      if (pendingSteps > 0) {
        const lastAnimatedValue = Number(summary.lastAnimatedStep ?? -1);
        return pathPointForValue(Number.isFinite(lastAnimatedValue) ? lastAnimatedValue : 0) || boardPointFromStep(0) || boardEntryPoint();
      }
      const focusValue = typeof summary.positionStep === "number" ? summary.positionStep : -1;
      return focusValue >= 0
        ? pathPointForValue(focusValue)
        : pointForPlayer(state.bootstrap?.self);
    }

    function pointToPercent(point, offsetX = 0, offsetY = 0) {
      if (!state.board || !point) return null;
      const size = boardSize();
      const x = ((point.x + offsetX) / size.width) * 100;
      const y = ((point.y + offsetY) / size.height) * 100;
      return {
        x: clamp(x, -8, 108).toFixed(4),
        y: clamp(y, -8, 108).toFixed(4)
      };
    }

    function scenePointFromBoardPoint(point) {
      if (!point) return null;
      return {
        x: state.camera.x + point.x * state.camera.scale,
        y: state.camera.y + point.y * state.camera.scale
      };
    }

    function viewportPointToBoard(clientX, clientY) {
      const rect = sceneRect();
      return {
        x: (clientX - rect.left - state.camera.x) / state.camera.scale,
        y: (clientY - rect.top - state.camera.y) / state.camera.scale
      };
    }

    function viewportCenter() {
      const rect = sceneRect();
      return {
        x: rect.width / 2,
        y: rect.height / 2
      };
    }

    // MILEAGE_VIEWPORT_CANVAS: every frame queries only the board rect visible in the viewport.
    function visibleBoardRect(paddingPx = 0) {
      const rect = sceneRect();
      const scale = Math.max(0.0001, state.camera.scale || 1);
      const paddingBoard = paddingPx / scale;
      return {
        left: ((0 - state.camera.x) / scale) - paddingBoard,
        top: ((0 - state.camera.y) / scale) - paddingBoard,
        right: ((rect.width - state.camera.x) / scale) + paddingBoard,
        bottom: ((rect.height - state.camera.y) / scale) + paddingBoard,
      };
    }

    function isBoardPointVisible(point, paddingPx = 0) {
      if (!point) return false;
      const rect = visibleBoardRect(paddingPx);
      return point.x >= rect.left && point.x <= rect.right && point.y >= rect.top && point.y <= rect.bottom;
    }

    function segmentVisible(segment, rect) {
      if (!segment || !rect) return false;
      const top = Number(segment.y || 0);
      const bottom = top + Math.max(1, Number(segment.h || 1));
      return bottom >= rect.top && top <= rect.bottom;
    }

    function visibleSegmentIds(rect = visibleBoardRect(0)) {
      return boardSegments()
        .filter((segment) => segmentVisible(segment, rect))
        .map((segment) => String(segment.id || ""));
    }

    function computeScales() {
      const rect = sceneRect();
      const size = boardSize();
      const fitWidth = rect.width / size.width;
      const fitHeight = rect.height / size.height;
      const fitWhole = Math.min(fitWidth, fitHeight);
      const compactViewport = rect.width <= 560;
      const detailMin = Math.max(fitWidth * (compactViewport ? 1.26 : 1.16), 0.6);
      const detailPreferred = fitWidth * (compactViewport ? 1.94 : 1.5);
      const detailScale = clamp(detailPreferred, detailMin, Math.max(detailMin, 1.24));
      const minScale = Math.max(fitWidth, fitWhole);
      const maxScale = Math.max(2.8, detailScale * 2.2, fitWidth * 5);
      const walkScale = clamp(lerp(minScale, detailScale, compactViewport ? 0.82 : 0.88), minScale, maxScale);
      state.camera.minScale = Math.min(minScale, detailScale);
      state.camera.maxScale = maxScale;
      state.camera.detailScale = clamp(detailScale, state.camera.minScale, state.camera.maxScale);
      state.camera.walkScale = clamp(walkScale, state.camera.minScale, state.camera.maxScale);
    }

    function clampCamera() {
      if (!state.board) return;
      const rect = sceneRect();
      const size = boardSize();
      const scaledWidth = size.width * state.camera.scale;
      const scaledHeight = size.height * state.camera.scale;
      if (scaledWidth <= rect.width) {
        state.camera.x = (rect.width - scaledWidth) / 2;
      } else {
        state.camera.x = clamp(state.camera.x, rect.width - scaledWidth, 0);
      }
      if (scaledHeight <= rect.height) {
        state.camera.y = (rect.height - scaledHeight) / 2;
      } else {
        state.camera.y = clamp(state.camera.y, rect.height - scaledHeight, 0);
      }
    }

    function applyCamera() {
      clampCamera();
      updateZoomSlider();
    }

    function isPathScrollAssistAvailable() {
      return false;
    }

    function projectPointOnSegment(target, start, end) {
      const vx = end.x - start.x;
      const vy = end.y - start.y;
      const lengthSq = (vx * vx) + (vy * vy);
      if (lengthSq <= 0.0001) {
        return {
          x: start.x,
          y: start.y,
          t: 0
        };
      }
      const t = clamp((((target.x - start.x) * vx) + ((target.y - start.y) * vy)) / lengthSq, 0, 1);
      return {
        x: lerp(start.x, end.x, t),
        y: lerp(start.y, end.y, t),
        t
      };
    }

    function nearestPathProjection(targetPoint) {
      if (!targetPoint || !state.board?.steps?.length) return null;
      const firstPoint = boardPointFromStep(0);
      const entryPoint = boardEntryPoint() || firstPoint;
      if (!entryPoint || !firstPoint) return null;

      let previousPoint = entryPoint;
      let previousValue = boardEntryPoint() ? -1 : 0;
      let startIndex = boardEntryPoint() ? 0 : 1;
      let best = null;

      for (let index = startIndex; index < state.board.steps.length; index += 1) {
        const nextPoint = boardPointFromStep(index);
        if (!nextPoint) continue;
        const projected = projectPointOnSegment(targetPoint, previousPoint, nextPoint);
        const dx = targetPoint.x - projected.x;
        const dy = targetPoint.y - projected.y;
        const distanceSq = (dx * dx) + (dy * dy);
        if (!best || distanceSq < best.distanceSq) {
          best = {
            point: {
              x: projected.x,
              y: projected.y
            },
            value: lerp(previousValue, index, projected.t),
            distanceSq
          };
        }
        previousPoint = nextPoint;
        previousValue = index;
      }

      return best;
    }

    function cameraFocusBoardPoint(options = {}) {
      if (!state.board) return null;
      const rect = sceneRect();
      const alignX = options.alignX ?? 0.5;
      const alignY = options.alignY ?? 0.58;
      return viewportPointToBoard(rect.width * alignX, rect.height * alignY);
    }

    function pathAssistStrengthFromTravel(horizontalDelta, verticalDelta, options = {}) {
      return 0;
    }

    function shouldTouchPathLock(horizontalDelta, verticalDelta) {
      return false;
    }

    function applyPathScrollAssist(options = {}) {
      if (!isPathScrollAssistAvailable()) return false;
      const strength = clamp(options.strength ?? 0, 0, 1);
      const verticalStrength = clamp(options.verticalStrength ?? 0, 0, 1);
      const shouldLockY = Boolean(options.lockY) && verticalStrength > 0.001;
      if (strength <= 0.001 && !shouldLockY) return false;
      const alignX = options.alignX ?? 0.5;
      const alignY = options.alignY ?? 0.58;
      const focusPoint = options.focusPoint || cameraFocusBoardPoint({ alignX, alignY });
      const projection = nearestPathProjection(focusPoint);
      if (!projection?.point) return false;
      let targetPoint = projection.point;
      if (options.snapToStep) {
        const snappedPoint = pathPointForValue(Math.round(projection.value));
        if (snappedPoint) {
          const snapBlend = clamp(options.snapBlend ?? 1, 0, 1);
          targetPoint = {
            x: lerp(targetPoint.x, snappedPoint.x, snapBlend),
            y: lerp(targetPoint.y, snappedPoint.y, snapBlend)
          };
        }
      }
      const rect = sceneRect();
      const wantedX = rect.width * alignX - targetPoint.x * state.camera.scale;
      const wantedY = rect.height * alignY - targetPoint.y * state.camera.scale;
      if (strength > 0.001) {
        state.camera.x = lerp(state.camera.x, wantedX, strength);
      }
      if (shouldLockY) {
        state.camera.y = lerp(state.camera.y, wantedY, verticalStrength);
      }
      return true;
    }

    function stopCameraMotion() {
      state.cameraMotion.active = false;
      state.cameraMotion.vx = 0;
      state.cameraMotion.vy = 0;
      state.cameraMotion.followPathX = false;
      state.cameraMotion.lockPathY = false;
      state.cameraMotion.followStrength = 0;
      state.cameraMotion.verticalStrength = 0;
      state.cameraMotion.alignY = 0.58;
      state.cameraMotion.snapToStep = false;
      state.cameraTween.active = false;
    }

    function updateCameraMomentum(ts) {
      if (!state.cameraMotion.active || state.walk.active || state.gesture.pointers.size > 0) return;
      const lastAt = state.cameraMotion.lastAt || ts;
      const dt = Math.min(0.035, Math.max(0.001, (ts - lastAt) / 1000));
      state.cameraMotion.lastAt = ts;
      state.camera.x += state.cameraMotion.vx * dt;
      state.camera.y += state.cameraMotion.vy * dt;
      const beforeClampX = state.camera.x;
      const beforeClampY = state.camera.y;
      clampCamera();
      const hitXEdge = Math.abs(beforeClampX - state.camera.x) > 0.01;
      const hitYEdge = Math.abs(beforeClampY - state.camera.y) > 0.01;
      if (hitXEdge) state.cameraMotion.vx = 0;
      if (hitYEdge) state.cameraMotion.vy = 0;
      const friction = Math.pow(0.055, dt);
      state.cameraMotion.vx *= friction;
      state.cameraMotion.vy *= friction;
      if (state.cameraMotion.followPathX || state.cameraMotion.lockPathY) {
        applyPathScrollAssist({
          strength: state.cameraMotion.followPathX ? state.cameraMotion.followStrength : 0,
          lockY: state.cameraMotion.lockPathY,
          verticalStrength: state.cameraMotion.verticalStrength,
          alignY: state.cameraMotion.alignY,
          snapToStep: state.cameraMotion.snapToStep,
          snapBlend: state.cameraMotion.snapToStep ? 0.74 : 0
        });
      }
      if (Math.hypot(state.cameraMotion.vx, state.cameraMotion.vy) < 8) {
        stopCameraMotion();
      }
      applyCamera();
    }

    function cameraTargetForPoint(point, options = {}) {
      const rect = sceneRect();
      const scale = clamp(options.scale ?? state.camera.scale, state.camera.minScale, state.camera.maxScale);
      const alignX = options.alignX ?? 0.5;
      const alignY = options.alignY ?? 0.68;
      return {
        scale,
        x: rect.width * alignX - point.x * scale,
        y: rect.height * alignY - point.y * scale
      };
    }

    function animateCameraToPoint(point, options = {}) {
      if (!point || !state.board) return;
      const target = cameraTargetForPoint(point, options);
      stopCameraMotion();
      state.cameraTween = {
        active: true,
        startAt: animationNow(),
        duration: Math.max(180, Number(options.duration || 620)),
        fromX: state.camera.x,
        fromY: state.camera.y,
        fromScale: state.camera.scale,
        toX: target.x,
        toY: target.y,
        toScale: target.scale
      };
    }

    function updateCameraTween(ts) {
      if (!state.cameraTween.active) return;
      const t = clamp((ts - state.cameraTween.startAt) / Math.max(1, state.cameraTween.duration), 0, 1);
      const eased = 1 - Math.pow(1 - t, 4);
      state.camera.scale = lerp(state.cameraTween.fromScale, state.cameraTween.toScale, eased);
      state.camera.x = lerp(state.cameraTween.fromX, state.cameraTween.toX, eased);
      state.camera.y = lerp(state.cameraTween.fromY, state.cameraTween.toY, eased);
      applyCamera();
      if (t >= 1) {
        state.cameraTween.active = false;
      }
    }

    function setCameraScale(nextScale, anchor = viewportCenter()) {
      if (!state.boardReady && !state.board) return;
      state.cameraTween.active = false;
      const scale = clamp(nextScale, state.camera.minScale, state.camera.maxScale);
      const boardAnchor = {
        x: (anchor.x - state.camera.x) / state.camera.scale,
        y: (anchor.y - state.camera.y) / state.camera.scale
      };
      state.camera.scale = scale;
      state.camera.x = anchor.x - boardAnchor.x * scale;
      state.camera.y = anchor.y - boardAnchor.y * scale;
      applyCamera();
    }

    function setCameraToPoint(point, options = {}) {
      if (!point || !state.board) return;
      const {
        scale = state.camera.scale,
        alignX = 0.5,
        alignY = 0.68,
        immediate = true
      } = options;
      const target = cameraTargetForPoint(point, { scale, alignX, alignY });
      state.camera.scale = target.scale;
      state.camera.x = target.x;
      state.camera.y = target.y;
      applyCamera();
      if (immediate) {
        renderBoardCanvas();
      }
    }

    function nudgeCameraToPoint(point, options = {}) {
      if (!point || !state.board) return;
      const rect = sceneRect();
      const alignX = options.alignX ?? 0.5;
      const alignY = options.alignY ?? 0.68;
      const followStrength = options.followStrength ?? 0.18;
      const wantedX = rect.width * alignX - point.x * state.camera.scale;
      const wantedY = rect.height * alignY - point.y * state.camera.scale;
      state.camera.x += (wantedX - state.camera.x) * followStrength;
      state.camera.y += (wantedY - state.camera.y) * followStrength;
      applyCamera();
    }

    function focusOnSelf(close = true, smooth = false, scaleOverride = null) {
      const point = currentSelfPoint() || boardEntryPoint();
      if (!point) return;
      const targetScale = clamp(
        scaleOverride ?? (close ? state.camera.detailScale : state.camera.minScale),
        state.camera.minScale,
        state.camera.maxScale
      );
      const wideView = targetScale <= (state.camera.minScale + 0.04);
      const options = {
        scale: targetScale,
        alignY: wideView ? 0.6 : 0.7
      };
      if (smooth) {
        animateCameraToPoint(point, options);
        return;
      }
      setCameraToPoint(point, options);
    }

    function focusOnWalkArea(close = true, smooth = false, scaleOverride = null) {
      const summary = state.bootstrap?.summary || {};
      const pendingStart = Number.isFinite(Number(summary.lastAnimatedStep ?? -1))
        ? Number(summary.lastAnimatedStep ?? -1)
        : -1;
      const point = pathPointForValue(pendingStart) || currentSelfPoint() || boardEntryPoint();
      if (!point) return;
      const targetScale = clamp(
        scaleOverride ?? (close ? state.camera.walkScale : state.camera.minScale),
        state.camera.minScale,
        state.camera.maxScale
      );
      const options = {
        scale: targetScale,
        alignY: targetScale <= (state.camera.minScale + 0.04) ? 0.58 : 0.66
      };
      if (smooth) {
        animateCameraToPoint(point, options);
        return;
      }
      setCameraToPoint(point, options);
    }

    function updateWalkCameraFollow(ts = animationNow()) {
      if (!state.walk.active) return;
      if (state.gesture.pointers.size > 0) return;
      if (state.leaderboardOpen || state.stepPlayersPanel.open) return;
      if (ts < Number(state.gesture.manualOverrideUntil || 0)) return;
      const point = currentSelfPoint();
      if (!point) return;
      const targetScale = clamp(
        Math.max(state.camera.scale, state.camera.walkScale),
        state.camera.minScale,
        state.camera.maxScale
      );
      if (!state.cameraTween.active && state.camera.scale < targetScale - 0.01) {
        setCameraScale(lerp(state.camera.scale, targetScale, 0.08), viewportCenter());
      }
      nudgeCameraToPoint(point, {
        alignY: state.camera.scale <= (state.camera.minScale + 0.04) ? 0.58 : 0.66,
        followStrength: state.walk.boost ? 0.24 : 0.17
      });
    }

    function updateZoomSlider() {
      if (!zoomSlider) return;
      const range = state.camera.maxScale - state.camera.minScale;
      const value = range > 0
        ? ((state.camera.scale - state.camera.minScale) / range) * 1000
        : 500;
      zoomSlider.value = String(Math.round(clamp(value, 0, 1000)));
    }

    function scaleFromZoomSlider() {
      const t = Number(zoomSlider.value || 0) / 1000;
      return state.camera.minScale + (state.camera.maxScale - state.camera.minScale) * t;
    }

    function updateSummary() {
      const summary = state.bootstrap?.summary;
      if (!summary) return;
      const pills = [
        `<div class="pill"><strong>สะสม</strong> ${Number(summary.lifetimeSteps || 0).toLocaleString()} ช่อง</div>`,
        `<div class="pill"><strong>ค้างเดิน</strong> ${Number(summary.pendingSteps || 0).toLocaleString()} ช่อง</div>`,
        `<div class="pill"><strong>รางวัล</strong> ${Number(summary.claimableRewardCount || 0).toLocaleString()} จุด</div>`
      ];
      summaryStrip.innerHTML = pills.join("");
      subtitle.textContent = "";
    }

    function summaryClaimedRewardIds(summary = null) {
      if (!summary || !Array.isArray(summary.claimedRewardIds)) {
        return [];
      }
      return summary.claimedRewardIds
        .map((rewardId) => String(rewardId || "").trim())
        .filter((rewardId) => rewardId !== "");
    }

    function syncClaimedRewardState(summary = null) {
      state.claimedRewardIds = new Set(summaryClaimedRewardIds(summary));
    }

    function rewardKindLabel(kind) {
      return {
        coin: "Coin",
        ticket: "Ticket",
        gem: "Gem",
        potion: "Potion",
        item: "Item"
      }[String(kind || "")] || "Reward";
    }

    function rewardClaimBalancesAfter(claimedRewards = []) {
      const balancesAfter = {};
      for (const reward of Array.isArray(claimedRewards) ? claimedRewards : []) {
        for (const walletRow of Array.isArray(reward?.walletRows) ? reward.walletRows : []) {
          const unitCode = String(walletRow?.unitCode || "").trim();
          if (unitCode === "") continue;
          const after = Number(walletRow?.walletBalanceAfter ?? walletRow?.balanceAmount);
          if (Number.isFinite(after)) {
            balancesAfter[unitCode] = Math.max(0, Math.trunc(after));
          }
        }
      }
      return balancesAfter;
    }

    function rewardClaimToastText(claimedRewards = []) {
      const parts = [];
      for (const reward of Array.isArray(claimedRewards) ? claimedRewards : []) {
        const amount = Math.max(1, Number(reward?.amount || 1));
        const kind = String(reward?.kind || "");
        if (kind === "item") {
          const itemName = String(reward?.inventory?.itemName || reward?.label || reward?.itemCode || "Item").trim();
          parts.push(`+${amount} ${itemName}`);
        } else {
          parts.push(`+${amount} ${rewardKindLabel(kind)}`);
        }
      }
      if (parts.length === 0) {
        return "";
      }
      if (parts.length <= 3) {
        return `รับแล้ว ${parts.join(", ")}`;
      }
      return `รับแล้ว ${parts.slice(0, 2).join(", ")} และอีก ${parts.length - 2} รางวัล`;
    }

    function normalizePickupUnit(unit) {
      const normalized = String(unit || "").trim().toLowerCase();
      return Object.prototype.hasOwnProperty.call(rewardPickupIcons, normalized) ? normalized : "";
    }

    function rewardPickupEntries(claimedRewards = []) {
      const totals = {};
      const add = (unit, amount) => {
        const normalized = normalizePickupUnit(unit);
        const value = Number(amount);
        if (!normalized || !Number.isFinite(value) || value <= 0) return;
        totals[normalized] = (totals[normalized] || 0) + value;
      };

      for (const reward of Array.isArray(claimedRewards) ? claimedRewards : []) {
        let usedWalletRows = false;
        for (const walletRow of Array.isArray(reward?.walletRows) ? reward.walletRows : []) {
          const unit = walletRow?.unitCode || walletRow?.unit || walletRow?.currency;
          const delta = Number(walletRow?.amountDelta ?? walletRow?.delta ?? 0);
          if (normalizePickupUnit(unit) && delta > 0) {
            add(unit, delta);
            usedWalletRows = true;
          }
        }
        if (!usedWalletRows) {
          add(reward?.kind, reward?.amount || 1);
        }
      }

      return Object.entries(totals).map(([unit, amount]) => ({
        unit,
        amount: Math.max(1, Math.trunc(amount))
      }));
    }

    function viewportPointFromBoardPoint(point) {
      if (!point) return null;
      const rect = sceneRect();
      return {
        x: rect.left + state.camera.x + point.x * state.camera.scale,
        y: rect.top + state.camera.y + point.y * state.camera.scale
      };
    }

    function rewardPickupSourcePoint(options = {}) {
      const sourcePoint = options.sourcePoint || null;
      const sourceX = Number(sourcePoint?.x);
      const sourceY = Number(sourcePoint?.y);
      if (Number.isFinite(sourceX) && Number.isFinite(sourceY)) {
        return { x: sourceX, y: sourceY };
      }
      return viewportPointFromBoardPoint(currentSelfPoint())
        || { x: window.innerWidth * 0.5, y: window.innerHeight * 0.58 };
    }

    function rewardPickupTargetPoint() {
      const target = summaryStrip?.getBoundingClientRect?.();
      if (target && target.width > 0 && target.height > 0) {
        return {
          x: target.left + target.width * 0.5,
          y: target.top + target.height * 0.5
        };
      }
      return {
        x: Math.max(52, window.innerWidth * 0.14),
        y: Math.max(42, window.innerHeight * 0.08)
      };
    }

    function spawnLocalPickupFlyers(unit, amount, sourcePoint, options = {}) {
      if (!pickupLayer) return;
      const normalized = normalizePickupUnit(unit);
      const iconSrc = rewardPickupIcons[normalized];
      if (!normalized || !iconSrc) return;

      const targetPoint = rewardPickupTargetPoint();
      const scale = clamp(Number(options.scale || 1.28), 0.7, 2.4);
      const countMultiplier = clamp(Number(options.countMultiplier || 1.4), 0.7, 3.2);
      const flyerCount = Math.min(30, Math.max(9, Math.round((Math.sqrt(Math.max(1, amount)) * 2.15 + 7) * countMultiplier)));
      const spread = 32 * scale;
      const lift = 78 * scale;
      const curveSide = (targetPoint.x >= sourcePoint.x ? 1 : -1) * 44 * scale;
      const size = Math.round(34 * scale);
      const durationBase = 1040 + Math.min(260, scale * 90);

      for (let index = 0; index < flyerCount; index += 1) {
        const wave = index / Math.max(1, flyerCount - 1);
        const angle = (index * 2.399963 + amount * 0.067) % (Math.PI * 2);
        const radius = spread * (0.24 + (index % 6) * 0.13);
        const fromX = sourcePoint.x + Math.cos(angle) * radius * 0.32;
        const fromY = sourcePoint.y + Math.sin(angle) * radius * 0.22;
        const burstX = sourcePoint.x + Math.cos(angle) * radius;
        const burstY = sourcePoint.y + Math.sin(angle) * radius * 0.62 - (index % 3) * 3 * scale;
        const floatX = burstX + Math.sin(angle * 1.8) * spread * 0.36;
        const floatY = burstY - lift - (index % 4) * 8 * scale;
        const midX = sourcePoint.x + (targetPoint.x - sourcePoint.x) * (0.52 + wave * 0.08) + curveSide * Math.sin(wave * Math.PI);
        const midY = sourcePoint.y + (targetPoint.y - sourcePoint.y) * 0.5 - (48 + (index % 4) * 11) * scale;
        const toX = targetPoint.x + (index % 3 - 1) * 4 * scale;
        const toY = targetPoint.y + ((index + 1) % 3 - 1) * 4 * scale;
        const flyer = document.createElement("div");
        const image = document.createElement("img");
        flyer.className = `pickup-coin is-${normalized}`;
        flyer.style.setProperty("--pickup-size", `${size}px`);
        flyer.style.setProperty("--from-x", `${fromX}px`);
        flyer.style.setProperty("--from-y", `${fromY}px`);
        flyer.style.setProperty("--burst-x", `${burstX}px`);
        flyer.style.setProperty("--burst-y", `${burstY}px`);
        flyer.style.setProperty("--float-x", `${floatX}px`);
        flyer.style.setProperty("--float-y", `${floatY}px`);
        flyer.style.setProperty("--mid-x", `${midX}px`);
        flyer.style.setProperty("--mid-y", `${midY}px`);
        flyer.style.setProperty("--to-x", `${toX}px`);
        flyer.style.setProperty("--to-y", `${toY}px`);
        flyer.style.setProperty("--pickup-delay", `${index * 24}ms`);
        flyer.style.setProperty("--pickup-duration", `${durationBase + Math.min(220, index * 14)}ms`);
        flyer.style.setProperty("--pickup-rotate-start", `${-32 + (index % 8) * 8}`);
        flyer.style.setProperty("--pickup-rotate-mid", `${22 - (index % 7) * 8}`);
        flyer.style.setProperty("--pickup-rotate-end", `${40 + (index % 9) * 9}`);
        image.src = iconSrc;
        image.alt = "";
        flyer.appendChild(image);
        pickupLayer.appendChild(flyer);
        const remove = () => flyer.remove();
        flyer.addEventListener("animationend", remove, { once: true });
        window.setTimeout(remove, durationBase + index * 24 + 800);
      }
    }

    function playLocalRewardPickup(claimedRewards = [], options = {}) {
      const entries = rewardPickupEntries(claimedRewards);
      if (entries.length === 0) return;
      const ui = boardUiSettings().currencyPickup;
      const sourcePoint = rewardPickupSourcePoint(options);
      let offset = 0;
      for (const entry of entries) {
        spawnLocalPickupFlyers(entry.unit, entry.amount, {
          x: sourcePoint.x + offset,
          y: sourcePoint.y + Math.abs(offset) * 0.16
        }, ui);
        offset += offset <= 0 ? 18 : -36;
      }
    }

    function markRecentlyClaimedRewards(claimedRewards = []) {
      const rewardIds = (Array.isArray(claimedRewards) ? claimedRewards : [])
        .map((reward) => String(reward?.id || "").trim())
        .filter((rewardId) => rewardId !== "");
      state.recentClaimedRewardIds = new Set(rewardIds);
      if (state.recentClaimRewardTimer) {
        window.clearTimeout(state.recentClaimRewardTimer);
      }
      if (rewardIds.length === 0) {
        state.recentClaimRewardTimer = 0;
        return;
      }
      state.recentClaimRewardTimer = window.setTimeout(() => {
        state.recentClaimedRewardIds = new Set();
        state.recentClaimRewardTimer = 0;
        renderRewardLayer();
      }, 1450);
    }

    function postSummaryToParent(extras = {}) {
      if (!window.parent || window.parent === window || !state.bootstrap?.summary) return;
      const ui = boardUiSettings();
      window.parent.postMessage({
        type: "gacha-mileage-summary",
        summary: state.bootstrap.summary,
        currencyPickup: ui.currencyPickup,
        ...extras
      }, "*");
    }

    function mileageOverlayOpen() {
      return Boolean(state.leaderboardOpen || state.stepPlayersPanel.open);
    }

    function syncMileageOverlayChrome() {
      viewer?.classList.toggle("has-overlay-open", mileageOverlayOpen());
    }

    function postMileageUiStateToParent(options = {}) {
      syncMileageOverlayChrome();
      if (!window.parent || window.parent === window) return;
      window.parent.postMessage({
        type: "gacha-mileage-ui-state",
        overlayOpen: mileageOverlayOpen(),
        clearFrameFade: true,
        ...options
      }, "*");
    }

    function rewardGlyph(kind) {
      return {
        coin: "C",
        ticket: "T",
        gem: "G",
        potion: "P",
        item: "I"
      }[kind] || "?";
    }

    function rewardMarkerSize(reward = null) {
      const rewardMeta = plainObject(reward?.meta) ? reward.meta : {};
      const rewardUi = plainObject(rewardMeta.ui) ? rewardMeta.ui : {};
      const rewardMarker = plainObject(rewardUi.rewardMarker) ? rewardUi.rewardMarker : {};
      return Math.round(clamp(Number(rewardMarker.size || markerStyle.rewardSize), 26, 110));
    }

    function rewardMarkerInnerMarkup(reward, claimed = false) {
      if (claimed) {
        return "<span>✓</span>";
      }
      const kind = String(reward?.kind || "coin");
      const iconSrc = rewardPickupIcons[kind] || "";
      if (iconSrc) {
        return `<span><img src="${escapeHtml(iconSrc)}" alt="" loading="lazy" decoding="async" /></span>`;
      }
      return `<span>${escapeHtml(rewardGlyph(kind))}</span>`;
    }

    function stepDisplayNumber(index) {
      const value = Number(index);
      return Number.isFinite(value) ? String(Math.max(0, Math.trunc(value))) : "0";
    }

    function stepBadgeOffset(point, index) {
      return {
        offsetX: 0,
        offsetY: 0
      };
    }

    function playerInitial(player) {
      const name = String(player?.displayName || "P").trim();
      return (name.charAt(0) || "P").toUpperCase();
    }

    function avatarInnerMarkup(player) {
      const avatarUrl = String(player?.avatarUrl || "").trim();
      if (avatarUrl !== "") {
        return `<img src="${escapeHtml(avatarUrl)}" alt="${escapeHtml(player?.displayName || "Player")}" loading="lazy" decoding="async" />`;
      }
      return `<span class="avatar-fallback">${escapeHtml(playerInitial(player))}</span>`;
    }

    function avatarMarkerMarkup(player, point, options = {}) {
      const {
        size = markerStyle.otherSize,
        liftPx = 0,
        offsetX = 0,
        offsetY = 0,
        self = false,
        scaleX = 1,
        scaleY = 1,
        rotateDeg = 0
      } = options;
      const pos = pointToPercent(point, offsetX, offsetY - liftPx);
      if (!pos) return "";
      return `
        <div
          class="avatar-marker${self ? " is-self" : ""}"
          style="left:${pos.x}%; top:${pos.y}%; --marker-size:${size}px; --marker-scale-x:${scaleX}; --marker-scale-y:${scaleY}; --marker-rotate:${rotateDeg}deg;"
        >
          ${avatarInnerMarkup(player)}
        </div>
      `;
    }

    function walkGroundEffectMarkup() {
      return "";
    }

    async function resolveManagedMileageAsset(src) {
      const runtime = window.AssetManifestRuntime;
      if (!runtime?.resolveAsset) {
        return { url: src };
      }
      try {
        return await runtime.resolveAsset("mileage-runtime", src, {});
      } catch (_error) {
        return { url: src };
      }
    }

    async function listManagedMileagePreloads() {
      const runtime = window.AssetManifestRuntime;
      if (!runtime?.listPageAssets) {
        return [];
      }
      try {
        return await runtime.listPageAssets("mileage-runtime", { preloadOnly: true });
      } catch (_error) {
        return [];
      }
    }

	    function cacheCanvasImage(src) {
      const key = String(src || "").trim();
      if (key === "") return null;
      if (state.canvas.imageCache.has(key)) {
        return state.canvas.imageCache.get(key);
	      }
	      const image = new Image();
	      image.decoding = "async";
	      image.__dekpokeCacheToken = `img${state.canvas.imageCache.size + 1}`;
	      const entry = {
        status: "loading",
        image,
        promise: null,
        resolvedUrl: ""
      };
      entry.promise = new Promise((resolve) => {
        image.addEventListener("load", () => {
          entry.status = "loaded";
          if (state.boardReady) {
            renderBoardCanvas();
          }
          resolve(image);
        }, { once: true });
        image.addEventListener("error", () => {
          entry.status = "error";
          resolve(null);
        }, { once: true });
      });
      Promise.resolve()
        .then(() => resolveManagedMileageAsset(key))
        .then((managed) => {
          entry.resolvedUrl = String(managed?.url || key);
          image.src = entry.resolvedUrl;
        })
        .catch(() => {
          entry.resolvedUrl = key;
          image.src = key;
        });
      state.canvas.imageCache.set(key, entry);
      return entry;
    }

	    function canvasImageOrNull(src) {
	      const entry = cacheCanvasImage(src);
	      return entry?.status === "loaded" ? entry.image : null;
	    }

	    function spriteEdgeFadePx(sprite, width, height) {
	      const meta = plainObject(sprite?.meta) ? sprite.meta : null;
	      const fallback = isUploadedSpritePath(sprite?.src) ? DEFAULT_UPLOADED_SPRITE_EDGE_FADE : 0;
	      const parsed = Number(meta?.edgeFade ?? sprite?.edgeFade ?? fallback);
	      const fade = clamp(Number.isFinite(parsed) ? parsed : fallback, 0, 64);
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
	        box.sx,
	        box.sy,
	        box.sw,
	        box.sh,
	        roundedWidth,
	        roundedHeight,
	        fadePx
	      ].join(":");
	      const cached = state.canvas.softSpriteCache.get(key);
	      if (cached) return cached;

	      const canvas = document.createElement("canvas");
	      canvas.width = roundedWidth;
	      canvas.height = roundedHeight;
	      const drawCtx = canvas.getContext("2d");
	      drawCtx.clearRect(0, 0, roundedWidth, roundedHeight);
	      drawCtx.drawImage(image, box.sx, box.sy, box.sw, box.sh, 0, 0, roundedWidth, roundedHeight);

	      const maskCanvas = document.createElement("canvas");
	      maskCanvas.width = roundedWidth;
	      maskCanvas.height = roundedHeight;
	      const maskCtx = maskCanvas.getContext("2d");
	      drawEdgeFadeMask(maskCtx, roundedWidth, roundedHeight, fadePx);

	      drawCtx.globalCompositeOperation = "destination-in";
	      drawCtx.drawImage(maskCanvas, 0, 0);
	      drawCtx.globalCompositeOperation = "source-over";

	      if (state.canvas.softSpriteCache.size > 320) {
	        state.canvas.softSpriteCache.clear();
	      }
	      state.canvas.softSpriteCache.set(key, canvas);
	      return canvas;
	    }

    function syncBoardCanvasSize() {
      const rect = sceneRect();
      const dpr = clamp(window.devicePixelRatio || 1, 1, 1.5);
      const width = Math.max(1, Math.ceil(rect.width * dpr));
      const height = Math.max(1, Math.ceil(rect.height * dpr));
      if (boardCanvas.width !== width || boardCanvas.height !== height) {
        boardCanvas.width = width;
        boardCanvas.height = height;
      }
      state.canvas.dpr = dpr;
      state.canvas.viewportWidth = width / dpr;
      state.canvas.viewportHeight = height / dpr;
    }

    function traceRoundedRect(ctx, x, y, width, height, radius) {
      const r = Math.min(radius, width / 2, height / 2);
      ctx.beginPath();
      ctx.moveTo(x + r, y);
      ctx.arcTo(x + width, y, x + width, y + height, r);
      ctx.arcTo(x + width, y + height, x, y + height, r);
      ctx.arcTo(x, y + height, x, y, r);
      ctx.arcTo(x, y, x + width, y, r);
      ctx.closePath();
    }

    function drawAvatarDisk(ctx, player, size, options = {}) {
      const radius = size / 2;
      const innerRadius = Math.max(6, radius - (options.borderWidth || 2));
      const avatarUrl = String(player?.avatarUrl || "").trim();
      ctx.save();
      ctx.shadowColor = options.shadowColor || "rgba(0, 0, 0, 0.34)";
      ctx.shadowBlur = options.shadowBlur || 14;
      ctx.fillStyle = options.outerFill || "#5d6f9c";
      ctx.beginPath();
      ctx.arc(0, 0, radius, 0, Math.PI * 2);
      ctx.fill();
      ctx.shadowBlur = 0;
      ctx.lineWidth = options.borderWidth || 2;
      ctx.strokeStyle = options.borderColor || "rgba(233, 243, 255, 0.94)";
      ctx.stroke();

      ctx.save();
      ctx.beginPath();
      ctx.arc(0, 0, innerRadius, 0, Math.PI * 2);
      ctx.clip();
      const avatarImage = canvasImageOrNull(avatarUrl);
      if (avatarImage) {
        ctx.drawImage(avatarImage, -innerRadius, -innerRadius, innerRadius * 2, innerRadius * 2);
      } else {
        const gradient = ctx.createLinearGradient(-radius, -radius, radius, radius);
        gradient.addColorStop(0, options.fallbackA || "#6879ff");
        gradient.addColorStop(1, options.fallbackB || "#ff91dc");
        ctx.fillStyle = gradient;
        ctx.fillRect(-innerRadius, -innerRadius, innerRadius * 2, innerRadius * 2);
        ctx.fillStyle = "rgba(245, 251, 255, 0.96)";
        ctx.font = `700 ${Math.max(10, size * 0.42)}px var(--font), system-ui, sans-serif`;
        ctx.textAlign = "center";
        ctx.textBaseline = "middle";
        ctx.fillText(playerInitial(player), 0, size * 0.02);
      }
      ctx.restore();
      ctx.restore();
    }

    function drawAvatarMarkerCanvas(ctx, player, point, options = {}) {
      if (!player || !point) return;
      const {
        size = markerStyle.otherSize,
        liftPx = 0,
        offsetX = 0,
        offsetY = 0,
        scaleX = 1,
        scaleY = 1,
        rotateDeg = 0,
        self = false
      } = options;
      ctx.save();
      ctx.translate(point.x + offsetX, point.y + offsetY - liftPx);
      ctx.rotate((rotateDeg * Math.PI) / 180);
      ctx.scale(scaleX, scaleY);
      drawAvatarDisk(ctx, player, size, {
        borderWidth: self ? 2.6 : 2,
        borderColor: self ? "rgba(245, 251, 255, 0.98)" : "rgba(233, 243, 255, 0.94)",
        shadowBlur: self ? 16 : 12,
        shadowColor: self ? "rgba(0, 0, 0, 0.38)" : "rgba(0, 0, 0, 0.32)"
      });
      ctx.restore();
    }

    function rewardTone(kind) {
      return {
        coin: "#ffd581",
        ticket: "#9ae9ff",
        gem: "#ffd2ff",
        potion: "#9cffbf",
        item: "#b9a0ff"
      }[String(kind || "")] || "#ffffff";
    }

    function drawRewardMarkerCanvas(ctx, reward, options = {}) {
      const point = rewardPoint(reward);
      if (!point) return;
      const claimed = Boolean(options.claimed);
      const unlocked = Boolean(options.unlocked);
      const justClaimed = Boolean(options.justClaimed);
      const ts = Number(options.ts || 0) || 0;
      const kind = String(reward?.kind || "coin");
      const size = rewardMarkerSize(reward);
      const radius = size / 2;
      const tone = rewardTone(kind);
      const scale = justClaimed ? 1.04 + Math.sin(ts / 120) * 0.04 : 1;

      ctx.save();
      ctx.translate(point.x, point.y);
      ctx.scale(scale, scale);
      ctx.shadowColor = "rgba(0, 0, 0, 0.34)";
      ctx.shadowBlur = 18;
      const fill = ctx.createLinearGradient(0, -radius, 0, radius);
      fill.addColorStop(0, unlocked ? "rgba(42, 52, 96, 0.92)" : "rgba(18, 24, 46, 0.9)");
      fill.addColorStop(1, unlocked ? "rgba(10, 14, 32, 0.9)" : "rgba(6, 10, 22, 0.88)");
      ctx.fillStyle = fill;
      ctx.beginPath();
      ctx.arc(0, 0, radius, 0, Math.PI * 2);
      ctx.fill();
      ctx.shadowBlur = 0;
      ctx.lineWidth = Math.max(2, size * 0.085);
      ctx.strokeStyle = claimed ? "rgba(235, 243, 255, 0.68)" : tone;
      ctx.stroke();

      ctx.beginPath();
      ctx.arc(0, 0, radius * 0.72, 0, Math.PI * 2);
      ctx.fillStyle = "rgba(255, 255, 255, 0.1)";
      ctx.fill();

      if (claimed) {
        ctx.fillStyle = "rgba(245, 251, 255, 0.94)";
        ctx.font = `700 ${Math.max(13, size * 0.44)}px var(--font), system-ui, sans-serif`;
        ctx.textAlign = "center";
        ctx.textBaseline = "middle";
        ctx.fillText("✓", 0, size * 0.02);
      } else {
        const iconSrc = rewardPickupIcons[kind] || "";
        const rewardIcon = canvasImageOrNull(iconSrc);
        if (rewardIcon) {
          const inner = radius * 0.72;
          ctx.save();
          ctx.beginPath();
          ctx.arc(0, 0, inner, 0, Math.PI * 2);
          ctx.clip();
          ctx.drawImage(rewardIcon, -inner, -inner, inner * 2, inner * 2);
          ctx.restore();
        } else {
          ctx.fillStyle = tone;
          ctx.font = `700 ${Math.max(13, size * 0.44)}px var(--font), system-ui, sans-serif`;
          ctx.textAlign = "center";
          ctx.textBaseline = "middle";
          ctx.fillText(rewardGlyph(kind), 0, size * 0.02);
        }
      }
      ctx.restore();
    }

    function drawIconTemplateRewardCanvas(ctx, reward, template, options = {}) {
      const point = rewardPoint(reward);
      const image = canvasImageOrNull(template?.src);
      if (!point || !image) {
        cacheCanvasImage(template?.src);
        return false;
      }
      const frame = spriteFrameIndex(template, Number(options.ts || 0) || animationNow());
      const box = spriteFrameBox(template, image, frame);
      const scale = Math.max(0.1, Number(template?.scale || 1));
      const width = Math.max(1, Number(template?.width || template?.frameWidth || box.sw || 40) * scale);
      const height = Math.max(1, Number(template?.height || template?.frameHeight || box.sh || 40) * scale);
      const anchorX = clamp(Number(template?.anchorX ?? 0.5), 0, 1);
      const anchorY = clamp(Number(template?.anchorY ?? 0.5), 0, 1);
      const offsetX = Number(template?.offsetX || 0);
      const offsetY = Number(template?.offsetY || 0);
      const claimed = Boolean(options.claimed);
      const unlocked = Boolean(options.unlocked);
      const justClaimed = Boolean(options.justClaimed);
      const ts = Number(options.ts || 0) || 0;
      const pulse = justClaimed ? 1.05 + Math.sin(ts / 120) * 0.05 : 1;

      ctx.save();
      ctx.translate(point.x + offsetX, point.y + offsetY);
      ctx.scale(pulse, pulse);
      ctx.shadowColor = "rgba(0, 0, 0, 0.34)";
      ctx.shadowBlur = 16;
      ctx.drawImage(
        image,
        box.sx,
        box.sy,
        box.sw,
        box.sh,
        -width * anchorX,
        -height * anchorY,
        width,
        height
      );
      ctx.shadowBlur = 0;
      if (unlocked && !claimed) {
        ctx.lineWidth = Math.max(2, Math.min(width, height) * 0.07);
        ctx.strokeStyle = "rgba(255, 213, 129, 0.9)";
        ctx.beginPath();
        ctx.ellipse(0, 0, (width * 0.56), (height * 0.56), 0, 0, Math.PI * 2);
        ctx.stroke();
      }
      if (claimed) {
        const badge = Math.max(15, Math.min(width, height) * 0.32);
        ctx.fillStyle = "rgba(18, 24, 46, 0.86)";
        ctx.strokeStyle = "rgba(235, 243, 255, 0.88)";
        ctx.lineWidth = 1.5;
        ctx.beginPath();
        ctx.arc(width * (1 - anchorX) - badge * 0.42, -height * anchorY + badge * 0.5, badge / 2, 0, Math.PI * 2);
        ctx.fill();
        ctx.stroke();
        ctx.fillStyle = "rgba(245, 251, 255, 0.96)";
        ctx.font = `700 ${Math.max(10, badge * 0.62)}px var(--font), system-ui, sans-serif`;
        ctx.textAlign = "center";
        ctx.textBaseline = "middle";
        ctx.fillText("✓", width * (1 - anchorX) - badge * 0.42, -height * anchorY + badge * 0.52);
      }
      ctx.restore();
      return true;
    }

    function drawRewardNodeCanvas(ctx, reward, options = {}) {
      const template = iconTemplateById(reward?.iconTemplateId);
      if (template && drawIconTemplateRewardCanvas(ctx, reward, template, options)) {
        return;
      }
      drawRewardMarkerCanvas(ctx, reward, options);
    }

    function buildStepGroups() {
      const steps = Array.isArray(state.board?.steps) ? state.board.steps : [];
      const groups = new Map();
      steps.forEach((step, index) => {
        const point = boardPointFromStep(index);
        if (!point) return;
        const key = `${Math.round(point.x * 10) / 10}:${Math.round(point.y * 10) / 10}`;
        if (!groups.has(key)) {
          groups.set(key, {
            key,
            point,
            indexes: [],
            segmentId: String(step?.segmentId || boardSegmentById(step?.segmentId)?.id || "")
          });
        }
        groups.get(key).indexes.push(index);
      });
      return Array.from(groups.values());
    }

    function pushRenderBucket(bucketMap, segmentId, value) {
      const key = String(segmentId || "");
      if (!bucketMap.has(key)) {
        bucketMap.set(key, []);
      }
      bucketMap.get(key).push(value);
    }

    function rebuildRenderIndex() {
      const stepGroupsBySegment = new Map();
      const rewardsBySegment = new Map();
      const spritesBySegment = new Map();
      const rewardStepIndexes = new Set();

      for (const group of buildStepGroups()) {
        pushRenderBucket(stepGroupsBySegment, group.segmentId, group);
      }

      for (const reward of Array.isArray(state.board?.rewards) ? state.board.rewards : []) {
        pushRenderBucket(rewardsBySegment, reward?.segmentId || boardSegmentById(reward?.segmentId)?.id || "", reward);
        if (Number.isInteger(reward?.stepIndex) && Number(reward.stepIndex) >= 0) {
          rewardStepIndexes.add(Number(reward.stepIndex));
        }
      }

      for (const node of Array.isArray(state.board?.rewardNodes) ? state.board.rewardNodes : []) {
        const reward = rewardNodeDrawable(node);
        pushRenderBucket(rewardsBySegment, reward?.segmentId || boardSegmentById(reward?.segmentId)?.id || "", reward);
        if (Number.isInteger(reward?.stepIndex) && Number(reward.stepIndex) >= 0) {
          rewardStepIndexes.add(Number(reward.stepIndex));
        }
      }

      for (const sprite of Array.isArray(state.board?.sprites) ? state.board.sprites : []) {
        pushRenderBucket(spritesBySegment, sprite?.segmentId || boardSegmentById(sprite?.segmentId)?.id || "", sprite);
      }

      state.renderIndex = {
        stepGroupsBySegment,
        rewardsBySegment,
        spritesBySegment,
        rewardStepIndexes
      };
    }

    function bucketEntries(bucketMap, segmentIds) {
      const out = [];
      for (const segmentId of segmentIds) {
        const entries = bucketMap.get(String(segmentId || ""));
        if (Array.isArray(entries) && entries.length > 0) {
          out.push(...entries);
        }
      }
      return out;
    }

    function drawSingleStepBadgeCanvas(ctx, group) {
      const firstIndex = group.indexes[0] || 0;
      const { offsetX, offsetY } = stepBadgeOffset(group.point, firstIndex);
      const x = group.point.x + offsetX;
      const y = group.point.y + offsetY;
      const size = markerStyle.stepBadgeSize;
      const radius = size / 2;
      ctx.save();
      ctx.translate(x, y);
      ctx.shadowColor = "rgba(0, 0, 0, 0.22)";
      ctx.shadowBlur = 16;
      ctx.fillStyle = "rgba(7, 11, 28, 0.34)";
      ctx.beginPath();
      ctx.arc(0, 0, radius, 0, Math.PI * 2);
      ctx.fill();
      ctx.shadowBlur = 0;
      ctx.lineWidth = 1.5;
      ctx.strokeStyle = "rgba(235, 243, 255, 0.82)";
      ctx.stroke();
      ctx.fillStyle = "#f5fbff";
      ctx.font = `700 ${Math.max(10, size * 0.46)}px var(--font), system-ui, sans-serif`;
      ctx.textAlign = "center";
      ctx.textBaseline = "middle";
      ctx.fillText(stepDisplayNumber(firstIndex), 0, size * 0.03);
      ctx.restore();
    }

    function drawStackStepBadgeCanvas(ctx, group) {
      const firstIndex = group.indexes[0] || 0;
      const { offsetX, offsetY } = stepBadgeOffset(group.point, firstIndex);
      const x = group.point.x + offsetX;
      const y = group.point.y + offsetY;
      const labels = group.indexes.slice(0, 3).map((index) => stepDisplayNumber(index));
      const label = labels.join(" | ");
      const hasMore = group.indexes.length > labels.length;
      const displayLabel = hasMore ? `${label}...` : label;
      const width = Math.max(44, 14 + displayLabel.length * 6.8);
      const height = 20;
      ctx.save();
      ctx.translate(x - width / 2, y - height / 2);
      ctx.fillStyle = "rgba(7, 11, 28, 0.3)";
      ctx.strokeStyle = "rgba(235, 243, 255, 0.78)";
      ctx.lineWidth = 1;
      traceRoundedRect(ctx, 0, 0, width, height, 10);
      ctx.fill();
      ctx.stroke();
      ctx.fillStyle = "#f5fbff";
      ctx.font = `${displayLabel.length > 12 ? "700 7.4px" : "700 8.4px"} var(--font), system-ui, sans-serif`;
      ctx.textAlign = "center";
      ctx.textBaseline = "middle";
      ctx.fillText(displayLabel, width / 2, height / 2 + 0.4);
      ctx.restore();
    }

    function drawStepLayerCanvas(ctx, visibleRect) {
      const segmentIds = visibleSegmentIds(visibleRect);
      const groups = bucketEntries(state.renderIndex.stepGroupsBySegment, segmentIds)
        .filter((group) => isBoardPointVisible(group.point, 80));
      state.canvas.lastVisibleCounts.steps = groups.length;
      for (const group of groups) {
        if (group.indexes.length > 1) {
          drawStackStepBadgeCanvas(ctx, group);
        } else {
          drawSingleStepBadgeCanvas(ctx, group);
        }
      }
    }

    function drawRewardLayerCanvas(ctx, visibleRect, ts = 0) {
      const segmentIds = visibleSegmentIds(visibleRect);
      const rewards = bucketEntries(state.renderIndex.rewardsBySegment, segmentIds)
        .filter((reward) => isBoardPointVisible(rewardPoint(reward), 96));
      const reachedStep = Number(state.bootstrap?.summary?.positionStep ?? -1);
      const canClaim = !state.bootstrap?.requiresLogin;
      state.canvas.lastVisibleCounts.rewards = rewards.length;
      for (const reward of rewards) {
        const rewardId = String(reward.id || "").trim();
        const stepIndex = Number.isInteger(reward.stepIndex) ? Number(reward.stepIndex) : -1;
        const claimed = rewardId !== "" && state.claimedRewardIds.has(rewardId);
        const unlocked = canClaim && stepIndex >= 0 && reachedStep >= stepIndex && !claimed;
        const justClaimed = rewardId !== "" && state.recentClaimedRewardIds.has(rewardId);
        if (reward.__rewardNode) {
          drawRewardNodeCanvas(ctx, reward, { claimed, unlocked, justClaimed, ts });
        } else {
          drawRewardMarkerCanvas(ctx, reward, { claimed, unlocked, justClaimed, ts });
        }
      }
    }

    function spriteFrameBox(sprite, image, frame) {
      const columns = Math.max(1, Number(sprite?.columns || 1));
      const rows = Math.max(1, Number(sprite?.rows || 1));
      const sourceWidth = Math.max(1, Number(sprite?.frameWidth || 0) || image.naturalWidth / columns);
      const sourceHeight = Math.max(1, Number(sprite?.frameHeight || 0) || image.naturalHeight / rows);
      const col = frame % columns;
      const row = Math.floor(frame / columns) % rows;
      return {
        sx: Math.min(Math.max(0, image.naturalWidth - sourceWidth), col * sourceWidth + Number(sprite?.frameX || 0)),
        sy: Math.min(Math.max(0, image.naturalHeight - sourceHeight), row * sourceHeight + Number(sprite?.frameY || 0)),
        sw: sourceWidth,
        sh: sourceHeight
      };
    }

	    function drawSpriteLayerCanvas(ctx, visibleRect, ts = animationNow()) {
      const segmentIds = visibleSegmentIds(visibleRect);
      const sprites = bucketEntries(state.renderIndex.spritesBySegment, segmentIds)
        .filter((sprite) => isBoardPointVisible(spritePoint(sprite), Math.max(Number(sprite?.width || 48), Number(sprite?.height || 48))));
      state.canvas.lastVisibleCounts.sprites = sprites.length;
      for (const sprite of sprites) {
        const point = spritePoint(sprite);
        const image = canvasImageOrNull(sprite?.src);
        if (!point || !image) {
          cacheCanvasImage(sprite?.src);
          continue;
        }
	        const width = Math.max(1, Number(sprite.width || 48));
	        const height = Math.max(1, Number(sprite.height || 48));
	        const frame = spriteFrameIndex(sprite, ts);
	        const box = spriteFrameBox(sprite, image, frame);
	        const fadePx = spriteEdgeFadePx(sprite, width, height);
	        const renderImage = fadePx > 0 ? softSpriteFrame(image, box, width, height, fadePx) : image;
	        const sourceBox = fadePx > 0
	          ? { sx: 0, sy: 0, sw: Math.max(1, Math.round(width)), sh: Math.max(1, Math.round(height)) }
	          : box;
	        ctx.save();
	        ctx.shadowColor = "rgba(0, 0, 0, 0.28)";
	        ctx.shadowBlur = 18;
	        ctx.drawImage(
	          renderImage,
	          sourceBox.sx,
	          sourceBox.sy,
	          sourceBox.sw,
	          sourceBox.sh,
	          point.x - width / 2,
	          point.y - height / 2,
	          width,
          height
        );
        ctx.restore();
      }
    }

    function friendClusterSide(point) {
      const size = boardSize();
      return point.x < size.width * 0.55 ? 1 : -1;
    }

    function friendClusterSlot(point, index, total) {
      const side = friendClusterSide(point);
      const x = side * 46;
      const y = total > 1 ? (index === 0 ? -22 : 18) : -8;
      return { x, y };
    }

    function drawPlayerOverflowChipAtCanvas(ctx, label, x, y) {
      const width = Math.max(25, 12 + String(label).length * 7);
      const height = 19;
      ctx.save();
      ctx.translate(x - width / 2, y - height / 2);
      ctx.shadowColor = "rgba(0, 0, 0, 0.24)";
      ctx.shadowBlur = 10;
      ctx.fillStyle = "rgba(250, 245, 255, 0.82)";
      ctx.strokeStyle = "rgba(255, 255, 255, 0.84)";
      ctx.lineWidth = 1.1;
      traceRoundedRect(ctx, 0, 0, width, height, 9.5);
      ctx.fill();
      ctx.shadowBlur = 0;
      ctx.stroke();
      ctx.fillStyle = "#4d2868";
      ctx.font = "800 9.5px var(--font), system-ui, sans-serif";
      ctx.textAlign = "center";
      ctx.textBaseline = "middle";
      ctx.fillText(label, width / 2, height / 2 + 0.3);
      ctx.restore();
      return {
        x,
        y,
        radius: Math.max(width, height) * 0.72
      };
    }

    function playerOverflowChipPoint(point) {
      const side = friendClusterSide(point);
      return {
        x: point.x + side * 90,
        y: point.y + 36
      };
    }

    function drawPlayerOverflowChipCanvas(ctx, label, point, options = {}) {
      const chip = playerOverflowChipPoint(point);
      return drawPlayerOverflowChipAtCanvas(ctx, label, chip.x, chip.y);
    }

    function drawPlayerClusterChipLayerCanvas(ctx) {
      for (const target of state.canvas.playerClusterTargets || []) {
        drawPlayerOverflowChipAtCanvas(ctx, target.label || "+", target.x, target.y);
      }
    }

    function drawPlayerLayerCanvas(ctx, visibleRect) {
      const groups = clusterPlayers();
      let visiblePlayerCount = 0;
      state.canvas.playerClusterTargets = [];
      for (const [stepKey, players] of groups.entries()) {
        const point = stepKey === "__entry__"
          ? boardEntryPoint()
          : boardPointFromStep(Number(stepKey));
        if (!point || !isBoardPointVisible(point, 96)) continue;
        visiblePlayerCount += players.length;

        const shownPlayers = players.slice(0, 2);
        shownPlayers.forEach((player, index) => {
          const slot = friendClusterSlot(point, index, shownPlayers.length);
          drawAvatarMarkerCanvas(ctx, player, point, {
            size: markerStyle.otherSize,
            liftPx: markerStyle.otherLift,
            offsetX: slot.x,
            offsetY: slot.y
          });
        });

        if (players.length > shownPlayers.length && stepKey !== "__entry__") {
          const hiddenCount = players.length - shownPlayers.length;
          const label = players.length > 9 ? "9+" : `+${hiddenCount}`;
          const hit = drawPlayerOverflowChipCanvas(ctx, label, point);
          state.canvas.playerClusterTargets.push({
            stepIndex: Number(stepKey),
            count: players.length,
            label,
            x: hit.x,
            y: hit.y,
            radius: hit.radius
          });
        }
      }
      state.canvas.lastVisibleCounts.players = visiblePlayerCount;
    }

    function selfVisualBasePoint(basePoint = currentSelfPoint()) {
      if (!basePoint) return null;
      return {
        x: basePoint.x + (state.walk.active ? state.walk.visualOffsetX : 0),
        y: basePoint.y + (state.walk.active ? state.walk.visualOffsetY : 0)
      };
    }

    function selfAvatarCenterPoint(basePoint = currentSelfPoint(), ts = animationNow()) {
      const visualPoint = selfVisualBasePoint(basePoint);
      if (!visualPoint) return null;
      const liftPx = state.walk.active
        ? state.walk.visualLiftPx
        : markerStyle.selfLift + Math.sin(ts / 360) * 2;
      return {
        x: visualPoint.x,
        y: visualPoint.y - liftPx,
        liftPx
      };
    }

    function resetSelfFxTrail(ts = animationNow()) {
      const center = selfAvatarCenterPoint(currentSelfPoint(), ts);
      state.selfFx.lastPoint = center ? { x: center.x, y: center.y } : null;
      state.selfFx.lastUpdateAt = ts;
      state.selfFx.lastEmitAt = ts;
      state.selfFx.velocityX = 0;
      state.selfFx.velocityY = 0;
    }

    function acquireSelfParticle(limit) {
      const particles = state.selfFx.particles;
      for (const particle of particles) {
        if (!particle.active) return particle;
      }
      if (particles.length < limit) {
        const particle = { active: false };
        particles.push(particle);
        return particle;
      }
      let oldest = particles[0];
      for (const particle of particles) {
        if (Number(particle.bornAt || 0) < Number(oldest.bornAt || 0)) {
          oldest = particle;
        }
      }
      return oldest;
    }

    function emitSelfTrailParticles(center, velocity, ts) {
      if (!center || !state.walk.active) return;
      const speed = Math.hypot(velocity.x, velocity.y);
      if (speed < 10) return;
      const boosted = Boolean(state.walk.boost);
      const limit = boosted ? 45 : 86;
      const interval = boosted ? 42 : 26;
      if (ts - Number(state.selfFx.lastEmitAt || 0) < interval) return;
      state.selfFx.lastEmitAt = ts;

      const ux = velocity.x / speed;
      const uy = velocity.y / speed;
      const nx = -uy;
      const ny = ux;
      const count = boosted ? 1 : clamp(Math.floor(speed / 180) + 1, 1, 3);

      for (let index = 0; index < count; index += 1) {
        const particle = acquireSelfParticle(limit);
        const side = (seededNoise() - 0.5) * 13;
        const back = 7 + seededNoise() * 11;
        const drift = 18 + seededNoise() * 34;
        particle.active = true;
        particle.bornAt = ts;
        particle.life = (boosted ? 360 : 540) + seededNoise() * (boosted ? 120 : 220);
        particle.x = center.x - (ux * back) + (nx * side);
        particle.y = center.y - (uy * back) + (ny * side);
        particle.vx = (velocity.x * 0.065) - (ux * drift) + (nx * ((seededNoise() - 0.5) * 34));
        particle.vy = (velocity.y * 0.065) - (uy * drift) + (ny * ((seededNoise() - 0.5) * 34)) - (8 + seededNoise() * 16);
        particle.size = 1.15 + seededNoise() * 1.75;
        particle.spin = seededNoise() * Math.PI * 2;
        particle.tone = seededNoise();
        particle.kind = "trail";
        particle.drag = 0.08;
        particle.gravity = -10;
      }
    }

    function emitLandingBurstParticles(point, options = {}) {
      if (!point) return;
      const boosted = Boolean(options.boosted);
      const special = Boolean(options.special);
      const direction = Number(options.angle || 0);
      const count = special
        ? (boosted ? 7 : 12)
        : (boosted ? 5 : 8);
      const limit = special
        ? (boosted ? 64 : 104)
        : (boosted ? 48 : 72);
      for (let index = 0; index < count; index += 1) {
        const particle = acquireSelfParticle(limit);
        const spread = (Math.PI * 2 * index) / count + (seededNoise() - 0.5) * 0.72;
        const directionalBias = Math.cos(spread - direction) * 0.16;
        const speed = special
          ? (boosted ? 44 : 56) + seededNoise() * (boosted ? 38 : 58)
          : (boosted ? 32 : 40) + seededNoise() * (boosted ? 22 : 34);
        const liftKick = special
          ? (boosted ? 30 : 42) + seededNoise() * (boosted ? 22 : 32)
          : (boosted ? 22 : 30) + seededNoise() * (boosted ? 16 : 22);
        const startRadius = special
          ? 4 + seededNoise() * 10
          : 2 + seededNoise() * 6;
        particle.active = true;
        particle.kind = "burst";
        particle.bornAt = options.ts || animationNow();
        particle.life = special
          ? (boosted ? 500 : 700) + seededNoise() * (boosted ? 150 : 220)
          : (boosted ? 380 : 520) + seededNoise() * (boosted ? 110 : 160);
        particle.x = point.x + Math.cos(spread) * startRadius;
        particle.y = point.y + Math.sin(spread) * startRadius;
        particle.vx = Math.cos(spread) * speed * (1 + directionalBias);
        particle.vy = Math.sin(spread) * speed * 0.72 - liftKick;
        particle.size = (special ? 1.85 : 1.15) + seededNoise() * (special ? 2.75 : 1.75);
        particle.spin = seededNoise() * Math.PI * 2;
        particle.tone = seededNoise();
        particle.color = selfParticleToneColor(particle.tone, "burst");
        particle.alphaMul = special ? 1 : 0.62;
        particle.tailMul = special ? 1 : 0.72;
        particle.glowMul = special ? 1 : 0.74;
        particle.drag = special ? 0.16 : 0.22;
        particle.gravity = special ? 120 : 92;
      }
    }

    function stepHasReward(stepValue) {
      return state.renderIndex.rewardStepIndexes.has(Number(stepValue));
    }

    function updateSelfFx(ts = animationNow()) {
      const center = selfAvatarCenterPoint(currentSelfPoint(), ts);
      const lastAt = Number(state.selfFx.lastUpdateAt || 0);
      const dt = lastAt > 0 ? clamp((ts - lastAt) / 1000, 0, 0.05) : 0;

      for (const particle of state.selfFx.particles) {
        if (!particle.active || dt <= 0) continue;
        particle.x += particle.vx * dt;
        particle.y += particle.vy * dt;
        const drag = Math.pow(Number(particle.drag || 0.08), dt);
        particle.vx *= drag;
        particle.vy = (particle.vy * drag) + (Number(particle.gravity ?? -10) * dt);
        if (ts - Number(particle.bornAt || 0) > Number(particle.life || 1)) {
          particle.active = false;
        }
      }

      if (!center) {
        state.selfFx.lastPoint = null;
        state.selfFx.lastUpdateAt = ts;
        return;
      }

      if (dt > 0 && state.selfFx.lastPoint) {
        state.selfFx.velocityX = (center.x - state.selfFx.lastPoint.x) / dt;
        state.selfFx.velocityY = (center.y - state.selfFx.lastPoint.y) / dt;
      } else {
        state.selfFx.velocityX = 0;
        state.selfFx.velocityY = 0;
      }

      emitSelfTrailParticles(center, {
        x: state.selfFx.velocityX,
        y: state.selfFx.velocityY
      }, ts);

      state.selfFx.lastPoint = { x: center.x, y: center.y };
      state.selfFx.lastUpdateAt = ts;
    }

    function addLandingStepPulse(stepValue, ts = animationNow()) {
      const point = pathPointForValue(stepValue);
      if (!point) return;
      const previousPoint = pathPointForValue(stepValue - 1) || point;
      const dx = point.x - previousPoint.x;
      const dy = point.y - previousPoint.y;
      const distance = Math.hypot(dx, dy);
      const special = stepHasReward(stepValue);
      const pulse = {
        point,
        stepValue,
        startedAt: ts,
        impactAt: ts,
        duration: special
          ? (state.walk.boost ? 680 : 940)
          : (state.walk.boost ? 560 : 760),
        segmentDuration: 1,
        radius: special
          ? clamp(distance * 0.76, 58, 84)
          : clamp(distance * 0.62, 48, 68),
        angle: Math.atan2(dy, dx || 0.0001),
        special
      };
      state.selfFx.activeStepGlow = pulse;
      state.selfFx.landingPulse = pulse;
      emitLandingBurstParticles(point, {
        angle: pulse.angle,
        boosted: state.walk.boost,
        special,
        ts
      });
    }

    function drawSelfGroundFxCanvas(ctx, ts = animationNow(), visibleRect = visibleBoardRect(0)) {
      const glow = state.selfFx.activeStepGlow;
      if (!glow?.point) return;
      const age = ts - Number(glow.startedAt || 0);
      const duration = Math.max(1, Number(glow.duration || 1));
      const p = clamp(age / duration, 0, 1);
      if (p >= 1) {
        state.selfFx.activeStepGlow = null;
        state.selfFx.landingPulse = null;
        return;
      }
      if (!isBoardPointVisible(glow.point, 72)) return;

      const baseRadius = Number(glow.radius || 52);
      const grow = easeOutQuart(clamp(p / 0.24, 0, 1));
      const flash = Math.pow(1 - clamp(p / 0.22, 0, 1), 1.75);
      const hold = p < 0.24
        ? 1
        : Math.pow(1 - clamp((p - 0.24) / 0.76, 0, 1), 1.05);
      const tileRadius = baseRadius * lerp(0.24, 1, grow);

      ctx.save();
      ctx.globalCompositeOperation = "lighter";
      ctx.translate(glow.point.x, glow.point.y);
      ctx.shadowColor = `rgba(255, 92, 235, ${0.92 * hold + 0.48 * flash})`;
      ctx.shadowBlur = baseRadius * (0.72 + flash * 0.38);
      const bloom = ctx.createRadialGradient(0, 0, 1, 0, 0, tileRadius);
      bloom.addColorStop(0, `rgba(255, 255, 255, ${0.42 * hold + 0.88 * flash})`);
      bloom.addColorStop(0.22, `rgba(255, 255, 255, ${0.26 * hold + 0.48 * flash})`);
      bloom.addColorStop(0.46, `rgba(255, 132, 244, ${0.72 * hold})`);
      bloom.addColorStop(0.74, `rgba(115, 231, 255, ${0.32 * hold})`);
      bloom.addColorStop(1, "rgba(132, 86, 255, 0)");
      ctx.fillStyle = bloom;
      ctx.beginPath();
      ctx.arc(0, 0, tileRadius, 0, Math.PI * 2);
      ctx.fill();

      if (flash > 0.02) {
        ctx.shadowColor = `rgba(255, 255, 255, ${0.88 * flash})`;
        ctx.shadowBlur = baseRadius * 0.42;
        const flashCore = ctx.createRadialGradient(0, 0, 1, 0, 0, tileRadius * 0.62);
        flashCore.addColorStop(0, `rgba(255, 255, 255, ${0.92 * flash})`);
        flashCore.addColorStop(0.55, `rgba(255, 255, 255, ${0.46 * flash})`);
        flashCore.addColorStop(1, "rgba(255, 255, 255, 0)");
        ctx.fillStyle = flashCore;
        ctx.beginPath();
        ctx.arc(0, 0, tileRadius * 0.62, 0, Math.PI * 2);
        ctx.fill();
      }
      ctx.restore();
    }

    function drawSelfParticleFxCanvas(ctx, ts = animationNow()) {
      const particles = state.selfFx.particles;
      if (particles.length === 0) return;
      ctx.save();
      ctx.globalCompositeOperation = "lighter";
      ctx.lineCap = "round";
      for (const particle of particles) {
        if (!particle.active) continue;
        const age = clamp((ts - Number(particle.bornAt || 0)) / Math.max(1, Number(particle.life || 1)), 0, 1);
        if (age >= 1 || !isBoardPointVisible(particle, 80)) continue;
        const burst = particle.kind === "burst";
        const fade = burst
          ? Math.pow(1 - age, 1.28)
          : Math.pow(1 - age, 1.6);
        const twinkle = burst
          ? 0.9 + Math.sin((ts * 0.01) + Number(particle.spin || 0)) * 0.1
          : 0.78 + Math.sin((ts * 0.018) + Number(particle.spin || 0)) * 0.22;
        const alpha = fade * twinkle * (burst ? 0.92 : 0.82) * Number(particle.alphaMul || 1);
        const radius = Number(particle.size || 2) * (burst ? (1.16 + age * 1.05) : (0.78 + age * 0.62));
        const edge = particle.color || selfParticleToneColor(particle.tone, particle.kind);
        const speed = Math.hypot(particle.vx, particle.vy);
        const ux = speed > 0.001 ? particle.vx / speed : Math.cos(Number(particle.spin || 0));
        const uy = speed > 0.001 ? particle.vy / speed : Math.sin(Number(particle.spin || 0));
        const tail = radius * (burst ? (3.8 + age * 2.2) : (5.6 + age * 2.4)) * Number(particle.tailMul || 1);
        const tailGradient = ctx.createLinearGradient(
          particle.x - ux * tail,
          particle.y - uy * tail,
          particle.x + ux * radius,
          particle.y + uy * radius
        );
        tailGradient.addColorStop(0, `rgba(${edge}, 0)`);
        tailGradient.addColorStop(0.45, `rgba(${edge}, ${(burst ? 0.14 : 0.18) * alpha})`);
        tailGradient.addColorStop(1, `rgba(255, 255, 255, ${(burst ? 0.36 : 0.46) * alpha})`);
        ctx.strokeStyle = tailGradient;
        ctx.lineWidth = Math.max(0.55, radius * (burst ? 0.42 : 0.46));
        ctx.beginPath();
        ctx.moveTo(particle.x - ux * tail, particle.y - uy * tail);
        ctx.lineTo(particle.x + ux * radius, particle.y + uy * radius);
        ctx.stroke();

        const glowRadius = radius * (burst ? 3.6 : 2.2) * Number(particle.glowMul || 1);
        const gradient = ctx.createRadialGradient(particle.x, particle.y, 0.2, particle.x, particle.y, glowRadius);
        gradient.addColorStop(0, `rgba(255, 255, 255, ${(burst ? 0.62 : 0.82) * alpha})`);
        gradient.addColorStop(0.34, `rgba(${edge}, ${(burst ? 0.42 : 0.34) * alpha})`);
        gradient.addColorStop(0.68, `rgba(${edge}, ${burst ? 0.16 * alpha : 0})`);
        gradient.addColorStop(1, `rgba(${edge}, 0)`);
        ctx.fillStyle = gradient;
        ctx.beginPath();
        ctx.arc(particle.x, particle.y, glowRadius, 0, Math.PI * 2);
        ctx.fill();

        if (!burst && alpha > 0.12) {
          ctx.strokeStyle = `rgba(255, 255, 255, ${0.44 * alpha})`;
          ctx.lineWidth = Math.max(0.7, radius * 0.38);
          const ray = radius * 2.8;
          ctx.beginPath();
          ctx.moveTo(particle.x - ray, particle.y);
          ctx.lineTo(particle.x + ray, particle.y);
          ctx.moveTo(particle.x, particle.y - ray);
          ctx.lineTo(particle.x, particle.y + ray);
          ctx.stroke();
        }
      }
      ctx.restore();
    }

    function drawSelfAuraCanvas(ctx, center, options = {}) {
      if (!center) return;
      const {
        size = markerStyle.selfSize,
        scaleX = 1,
        scaleY = 1,
        rotateDeg = 0,
        ts = animationNow(),
        active = false
      } = options;
      const radius = size / 2;
      const pulse = 0.5 + Math.sin(ts / 360) * 0.5;
      const activeBoost = active ? 1 : 0;
      const speed = Math.hypot(state.selfFx.velocityX, state.selfFx.velocityY);
      const kinetic = clamp(speed / 360, 0, 1);
      const orbit = (ts * 0.0048) % (Math.PI * 2);

      ctx.save();
      ctx.translate(center.x, center.y);
      ctx.rotate((rotateDeg * Math.PI) / 180);
      ctx.scale(scaleX, scaleY);
      ctx.globalCompositeOperation = "lighter";
      const outer = radius * (1.42 + (pulse * 0.12) + activeBoost * 0.1 + kinetic * 0.16);
      const aura = ctx.createRadialGradient(0, 0, radius * 0.4, 0, 0, outer);
      aura.addColorStop(0, `rgba(255, 255, 255, ${0.18 + activeBoost * 0.08 + kinetic * 0.08})`);
      aura.addColorStop(0.38, `rgba(255, 178, 229, ${0.24 + activeBoost * 0.12 + kinetic * 0.08})`);
      aura.addColorStop(0.72, `rgba(128, 238, 255, ${0.12 + activeBoost * 0.08 + kinetic * 0.06})`);
      aura.addColorStop(1, "rgba(255, 205, 108, 0)");
      ctx.fillStyle = aura;
      ctx.beginPath();
      ctx.arc(0, 0, outer, 0, Math.PI * 2);
      ctx.fill();

      ctx.lineCap = "round";
      ctx.strokeStyle = `rgba(255, 255, 255, ${0.54 + activeBoost * 0.16 + kinetic * 0.18})`;
      ctx.lineWidth = 1.8 + kinetic * 1.1;
      ctx.beginPath();
      ctx.arc(0, 0, radius * 1.16, orbit, orbit + Math.PI * (0.72 + kinetic * 0.34));
      ctx.stroke();

      ctx.strokeStyle = `rgba(255, 126, 228, ${0.32 + activeBoost * 0.16 + kinetic * 0.14})`;
      ctx.lineWidth = 3.2 + kinetic * 1.4;
      ctx.beginPath();
      ctx.arc(0, 0, radius * 1.34, orbit + Math.PI * 0.88, orbit + Math.PI * (1.58 + kinetic * 0.26));
      ctx.stroke();

      ctx.strokeStyle = `rgba(115, 235, 255, ${0.28 + activeBoost * 0.12 + kinetic * 0.16})`;
      ctx.lineWidth = 1.45 + kinetic * 0.9;
      ctx.beginPath();
      ctx.arc(0, 0, radius * 1.5, -orbit * 0.74, -orbit * 0.74 + Math.PI * 0.58);
      ctx.stroke();
      ctx.restore();
    }

    function drawSelfOrbFxCanvas(ctx, center, options = {}) {
      if (!center) return;
      const {
        ts = animationNow(),
        size = markerStyle.selfSize,
        velocityX = 0,
        velocityY = 0,
        active = false
      } = options;
      const speed = Math.hypot(velocityX, velocityY);
      const ux = speed > 0.001 ? velocityX / speed : 1;
      const uy = speed > 0.001 ? velocityY / speed : 0;
      const stretch = clamp(speed / 420, 0, 1);
      const kineticScale = 1 + stretch * 0.18;
      const configs = [
        { phase: 0.1, speed: 0.0032, rx: 0.62, ry: 0.28, size: 3.6, tone: "255, 225, 142" },
        { phase: 2.25, speed: -0.0027, rx: 0.52, ry: 0.34, size: 3.05, tone: "132, 238, 255" },
        { phase: 4.38, speed: 0.0022, rx: 0.7, ry: 0.22, size: 2.85, tone: "255, 132, 224" },
        { phase: 5.42, speed: -0.0018, rx: 0.4, ry: 0.42, size: 2.25, tone: "255, 246, 205" }
      ];

      ctx.save();
      ctx.globalCompositeOperation = "lighter";
      for (const orb of configs) {
        const a = (ts * orb.speed) + orb.phase;
        const radiusX = size * orb.rx * kineticScale;
        const radiusY = size * orb.ry * kineticScale;
        const x = center.x + Math.cos(a) * radiusX + ux * stretch * 6;
        const y = center.y + Math.sin(a) * radiusY + uy * stretch * 3.8;
        const previousX = center.x + Math.cos(a - 0.34) * radiusX;
        const previousY = center.y + Math.sin(a - 0.34) * radiusY;
        const r = orb.size * (active ? 1.18 : 1) * kineticScale;
        const trail = ctx.createLinearGradient(previousX, previousY, x, y);
        trail.addColorStop(0, `rgba(${orb.tone}, 0)`);
        trail.addColorStop(0.72, `rgba(${orb.tone}, ${active ? 0.44 : 0.26})`);
        trail.addColorStop(1, "rgba(255, 255, 255, 0.72)");
        ctx.strokeStyle = trail;
        ctx.lineWidth = r * 0.82;
        ctx.beginPath();
        ctx.moveTo(previousX, previousY);
        ctx.lineTo(x, y);
        ctx.stroke();

        const glow = ctx.createRadialGradient(x, y, 0.2, x, y, r * 3.4);
        glow.addColorStop(0, "rgba(255, 255, 255, 0.98)");
        glow.addColorStop(0.3, `rgba(${orb.tone}, 0.74)`);
        glow.addColorStop(1, `rgba(${orb.tone}, 0)`);
        ctx.fillStyle = glow;
        ctx.beginPath();
        ctx.arc(x, y, r * 3.4, 0, Math.PI * 2);
        ctx.fill();
      }
      ctx.restore();
    }

    function drawSelfLayerCanvas(ctx, ts = animationNow()) {
      const selfPlayer = state.bootstrap?.self || null;
      const point = currentSelfPoint();
      if (!selfPlayer || !point || !isBoardPointVisible(point, 120)) return;
      const liftPx = state.walk.active
        ? state.walk.visualLiftPx
        : markerStyle.selfLift + Math.sin(ts / 360) * 2;
      const scaleX = state.walk.active ? state.walk.visualScaleX : 1;
      const scaleY = state.walk.active ? state.walk.visualScaleY : 1;
      const rotateDeg = state.walk.active ? state.walk.visualTiltDeg : Math.sin(ts / 520) * 0.8;
      const visualPoint = selfVisualBasePoint(point);
      const center = selfAvatarCenterPoint(point, ts);
      drawSelfAuraCanvas(ctx, center, {
        size: markerStyle.selfSize,
        scaleX,
        scaleY,
        rotateDeg,
        ts,
        active: state.walk.active
      });
      drawAvatarMarkerCanvas(ctx, selfPlayer, visualPoint, {
        size: markerStyle.selfSize,
        liftPx,
        scaleX,
        scaleY,
        rotateDeg,
        self: true
      });
      if (state.walk.active) {
        drawSelfOrbFxCanvas(ctx, center, {
          ts,
          size: markerStyle.selfSize,
          velocityX: state.selfFx.velocityX,
          velocityY: state.selfFx.velocityY,
          active: true
        });
      }
    }

    function ambientZoneAtY(y) {
      const size = boardSize();
      const t = clamp(y / Math.max(1, size.height), 0, 1);
      if (t < 0.17) return "rainbow";
      if (t >= 0.17 && t < 0.34) return "space";
      return "city";
    }

    function ambientOrbTone(zone, value) {
      if (zone === "rainbow") {
        const tones = [
          "255, 174, 229",
          "255, 221, 133",
          "132, 238, 255",
          "157, 255, 205",
          "198, 158, 255"
        ];
        return tones[Math.floor(clamp(value, 0, 0.999) * tones.length)] || tones[0];
      }
      if (value < 0.38) return "255, 220, 132";
      if (value < 0.72) return "127, 231, 255";
      return "255, 153, 226";
    }

    function updateAmbienceCameraState(ts = animationNow()) {
      const lastAt = Number(state.ambience.lastCameraAt || 0);
      const dt = lastAt > 0 ? clamp((ts - lastAt) / 1000, 0.001, 0.08) : 0;
      if (dt > 0) {
        const vx = (state.camera.x - Number(state.ambience.lastCameraX || 0)) / dt;
        const vy = (state.camera.y - Number(state.ambience.lastCameraY || 0)) / dt;
        state.ambience.cameraVelocityX = lerp(Number(state.ambience.cameraVelocityX || 0), vx, 0.22);
        state.ambience.cameraVelocityY = lerp(Number(state.ambience.cameraVelocityY || 0), vy, 0.22);
      } else {
        state.ambience.cameraVelocityX = 0;
        state.ambience.cameraVelocityY = 0;
      }
      state.ambience.lastCameraX = state.camera.x;
      state.ambience.lastCameraY = state.camera.y;
      state.ambience.lastCameraAt = ts;
    }

    function drawAmbientOrbFxCanvas(ctx, ts = animationNow(), visibleRect = visibleBoardRect(0)) {
      const size = boardSize();
      if (!size.width || !size.height) return;
      const scale = Math.max(0.2, state.camera.scale || 1);
      const viewportW = Math.max(1, Number(state.canvas.viewportWidth || boardCanvas?.clientWidth || 1));
      const viewportH = Math.max(1, Number(state.canvas.viewportHeight || boardCanvas?.clientHeight || 1));
      const visibleCenterX = (visibleRect.left + visibleRect.right) / 2;
      const visibleCenterY = (visibleRect.top + visibleRect.bottom) / 2;
      const cameraTrailX = clamp(-Number(state.ambience.cameraVelocityX || 0) * 0.048, -92, 92);
      const cameraTrailY = clamp(-Number(state.ambience.cameraVelocityY || 0) * 0.048, -92, 92);
      const cameraTrailPower = clamp(Math.hypot(cameraTrailX, cameraTrailY) / 92, 0, 1);
      const layers = [
        { cellW: 182, cellH: 228, density: 0.34, depth: 0.24, dx: 1, dy: -0.3, speed: 0.00013, alpha: 0.42 },
        { cellW: 226, cellH: 272, density: 0.28, depth: 0.18, dx: -1, dy: 0.22, speed: 0.0001, alpha: 0.34 },
        { cellW: 202, cellH: 248, density: 0.24, depth: 0.13, dx: 0.42, dy: 1, speed: 0.000085, alpha: 0.3 },
        { cellW: 264, cellH: 312, density: 0.2, depth: 0.09, dx: -0.34, dy: -1, speed: 0.000072, alpha: 0.24 }
      ];

      ctx.save();
      ctx.setTransform(state.canvas.dpr, 0, 0, state.canvas.dpr, 0, 0);
      ctx.globalCompositeOperation = "lighter";
      for (let layerIndex = 0; layerIndex < layers.length; layerIndex += 1) {
        const layer = layers[layerIndex];
        const depth = Number(layer.depth || 0);
        const parallaxPaddingX = 140 + viewportW * depth;
        const parallaxPaddingY = 170 + viewportH * depth;
        const startX = Math.floor((visibleRect.left - (parallaxPaddingX / scale)) / layer.cellW) - 1;
        const endX = Math.ceil((visibleRect.right + (parallaxPaddingX / scale)) / layer.cellW) + 1;
        const startY = Math.floor((visibleRect.top - (parallaxPaddingY / scale)) / layer.cellH) - 1;
        const endY = Math.ceil((visibleRect.bottom + (parallaxPaddingY / scale)) / layer.cellH) + 1;

        for (let cy = startY; cy <= endY; cy += 1) {
          for (let cx = startX; cx <= endX; cx += 1) {
            const seed = (cx * 73856093) ^ (cy * 19349663) ^ (layerIndex * 83492791) ^ state.ambience.seed;
            const gate = hashNoise(seed);
            const centerY = (cy + hashNoise(seed + 11)) * layer.cellH;
            const zone = ambientZoneAtY(centerY);
            if (zone === "space") continue;
            const zoneDensity = zone === "rainbow" ? layer.density * 1.2 : layer.density;
            if (gate > zoneDensity) continue;

            const centerX = (cx + hashNoise(seed + 7)) * layer.cellW;
            const phase = hashNoise(seed + 19) * Math.PI * 2;
            const drift = 10 + hashNoise(seed + 23) * 24;
            const wobbleX = Math.sin(ts * layer.speed + phase) * drift + Math.sin(ts * (layer.speed * 0.44) + phase * 1.6) * drift * 0.26;
            const wobbleY = Math.cos(ts * (layer.speed * 0.82) + phase) * drift * 0.55 + Math.cos(ts * (layer.speed * 0.31) + phase * 1.2) * drift * 0.18;
            const x = centerX + wobbleX + (layer.dx * Math.sin(ts * 0.00013 + phase) * 8);
            const y = centerY + wobbleY + (layer.dy * Math.cos(ts * 0.0001 + phase) * 10);

            const prevTs = ts - 220;
            const prevX = centerX
              + Math.sin(prevTs * layer.speed + phase) * drift
              + Math.sin(prevTs * (layer.speed * 0.44) + phase * 1.6) * drift * 0.26
              + (layer.dx * Math.sin(prevTs * 0.00013 + phase) * 8);
            const prevY = centerY
              + Math.cos(prevTs * (layer.speed * 0.82) + phase) * drift * 0.55
              + Math.cos(prevTs * (layer.speed * 0.31) + phase * 1.2) * drift * 0.18
              + (layer.dy * Math.cos(prevTs * 0.0001 + phase) * 10);
            const toViewportX = (boardX) => state.camera.x + (boardX * scale) + ((boardX - visibleCenterX) * scale * depth);
            const toViewportY = (boardY) => state.camera.y + (boardY * scale) + ((boardY - visibleCenterY) * scale * depth);
            const screenX = toViewportX(x);
            const screenY = toViewportY(y);
            if (screenX < -96 || screenX > viewportW + 96 || screenY < -112 || screenY > viewportH + 112) continue;

            const cameraLag = 0.54 + depth * 2.6 + layerIndex * 0.08;
            const prevScreenX = toViewportX(prevX) + cameraTrailX * cameraLag;
            const prevScreenY = toViewportY(prevY) + cameraTrailY * cameraLag;
            const tone = ambientOrbTone(zone, hashNoise(seed + 31));
            const twinkle = 0.7 + Math.sin(ts * (0.0024 + hashNoise(seed + 37) * 0.0019) + phase) * 0.24;
            const flutter = 0.76 + Math.sin(ts * (0.008 + hashNoise(seed + 59) * 0.0046) + phase * 1.4) * 0.24;
            const zoomLift = clamp(Math.pow(scale, 0.16), 0.84, 1.28);
            const radius = ((zone === "rainbow" ? 3.1 : 2.7) + hashNoise(seed + 43) * 2.7) * zoomLift * (1 + depth * 0.2);
            const glowRadius = radius * (zone === "rainbow" ? 7.6 : 6.6);
            const alpha = layer.alpha * twinkle * flutter * (zone === "rainbow" ? 1.08 : 0.94);
            const tail = ctx.createLinearGradient(prevScreenX, prevScreenY, screenX, screenY);
            tail.addColorStop(0, `rgba(${tone}, 0)`);
            tail.addColorStop(0.36, `rgba(${tone}, ${(0.14 + cameraTrailPower * 0.16) * alpha})`);
            tail.addColorStop(1, `rgba(255, 255, 255, ${(0.5 + cameraTrailPower * 0.22) * alpha})`);
            ctx.strokeStyle = tail;
            ctx.lineWidth = Math.max(0.7, radius * (0.5 + cameraTrailPower * 0.28));
            ctx.lineCap = "round";
            ctx.beginPath();
            ctx.moveTo(prevScreenX, prevScreenY);
            ctx.lineTo(screenX, screenY);
            ctx.stroke();

            const glow = ctx.createRadialGradient(screenX, screenY, 0.1, screenX, screenY, glowRadius);
            glow.addColorStop(0, `rgba(255, 255, 255, ${0.92 * alpha})`);
            glow.addColorStop(0.22, `rgba(${tone}, ${0.72 * alpha})`);
            glow.addColorStop(0.62, `rgba(${tone}, ${0.2 * alpha})`);
            glow.addColorStop(1, `rgba(${tone}, 0)`);
            ctx.fillStyle = glow;
            ctx.beginPath();
            ctx.arc(screenX, screenY, glowRadius, 0, Math.PI * 2);
            ctx.fill();

            ctx.fillStyle = `rgba(255, 255, 255, ${0.88 * alpha})`;
            ctx.beginPath();
            ctx.arc(screenX, screenY, Math.max(0.65, radius * 0.48), 0, Math.PI * 2);
            ctx.fill();
          }
        }
      }
      ctx.restore();
    }

    function drawCloudCapFxCanvas(ctx, ts = animationNow(), visibleRect = visibleBoardRect(0)) {
      const size = boardSize();
      const cloudLimit = 720;
      if (visibleRect.top > cloudLimit || visibleRect.bottom < -120) return;
      const scale = Math.max(0.2, state.camera.scale || 1);
      const topFade = clamp((cloudLimit - Math.max(0, visibleRect.top)) / cloudLimit, 0, 1);
      if (topFade <= 0) return;

      ctx.save();
      ctx.globalCompositeOperation = "screen";
      for (let index = 0; index < 42; index += 1) {
        const seed = state.ambience.cloudSeed + index * 811;
        const drift = Math.sin(ts * (0.000035 + hashNoise(seed + 3) * 0.00003) + hashNoise(seed + 5) * Math.PI * 2);
        const x = (hashNoise(seed + 7) * (size.width + 340)) - 170 + drift * (28 + hashNoise(seed + 11) * 42);
        const heightBias = Math.pow(hashNoise(seed + 13), 1.72);
        const y = -84 + heightBias * 760 + Math.cos(ts * 0.00004 + index) * 12;
        if (y > cloudLimit + 140 || y < visibleRect.top - 160 || y > visibleRect.bottom + 160) continue;
        const edgeFade = 1 - clamp((y - 440) / 300, 0, 0.76);
        const crownBoost = 1 - clamp(y / 220, 0, 1);
        const rx = (108 + hashNoise(seed + 17) * 196) / Math.pow(scale, 0.08);
        const ry = 30 + hashNoise(seed + 19) * 76 + crownBoost * 14;
        const alpha = (0.12 + hashNoise(seed + 23) * 0.18) * topFade * edgeFade * (0.84 + crownBoost * 0.58);
        const tone = hashNoise(seed + 29) < 0.46 ? "255, 232, 249" : "211, 247, 255";
        ctx.save();
        ctx.translate(x, y);
        ctx.scale(rx, ry);
        const fog = ctx.createRadialGradient(0, 0, 0.05, 0, 0, 1);
        fog.addColorStop(0, `rgba(255, 255, 255, ${alpha * 1.12})`);
        fog.addColorStop(0.46, `rgba(${tone}, ${alpha})`);
        fog.addColorStop(1, `rgba(${tone}, 0)`);
        ctx.fillStyle = fog;
        ctx.beginPath();
        ctx.arc(0, 0, 1, 0, Math.PI * 2);
        ctx.fill();
        ctx.restore();
      }

      const veilHeight = 520;
      const veil = ctx.createLinearGradient(0, -70, 0, veilHeight);
      veil.addColorStop(0, `rgba(255, 247, 255, ${0.52 * topFade})`);
      veil.addColorStop(0.18, `rgba(255, 241, 250, ${0.34 * topFade})`);
      veil.addColorStop(0.5, `rgba(212, 242, 255, ${0.18 * topFade})`);
      veil.addColorStop(1, "rgba(255, 255, 255, 0)");
      ctx.fillStyle = veil;
      ctx.fillRect(-80, -120, size.width + 160, veilHeight + 80);

      const crown = ctx.createLinearGradient(0, -40, 0, 260);
      crown.addColorStop(0, `rgba(255, 255, 255, ${0.44 * topFade})`);
      crown.addColorStop(0.52, `rgba(240, 244, 255, ${0.16 * topFade})`);
      crown.addColorStop(1, "rgba(255, 255, 255, 0)");
      ctx.fillStyle = crown;
      ctx.fillRect(-60, -60, size.width + 120, 260);
      ctx.restore();
    }

    function drawVisibleSegments(ctx, visibleRect) {
      const size = boardSize();
      const segments = boardSegments().filter((segment) => segmentVisible(segment, visibleRect));
      state.canvas.lastVisibleCounts.segments = segments.length;
      for (const segment of segments) {
        const image = canvasImageOrNull(segment?.src);
        if (!image) {
          cacheCanvasImage(segment?.src);
          continue;
        }
        const segmentTop = Number(segment.y || 0);
        const segmentHeight = Math.max(1, Number(segment.h || image.naturalHeight || 1));
        const drawTop = Math.max(segmentTop, visibleRect.top);
        const drawBottom = Math.min(segmentTop + segmentHeight, visibleRect.bottom);
        const drawHeight = drawBottom - drawTop;
        if (drawHeight <= 0.1) continue;
        const sourceY = ((drawTop - segmentTop) / segmentHeight) * Math.max(1, image.naturalHeight || segmentHeight);
        const sourceHeight = (drawHeight / segmentHeight) * Math.max(1, image.naturalHeight || segmentHeight);
        ctx.drawImage(
          image,
          0,
          sourceY,
          Math.max(1, image.naturalWidth || size.width),
          sourceHeight,
          0,
          drawTop,
          size.width,
          drawHeight
        );
      }
    }

    function boardDecorMotion(blueprint, ts = animationNow()) {
      const idle = plainObject(blueprint?.idle) ? blueprint.idle : {};
      const parallax = plainObject(blueprint?.parallax) ? blueprint.parallax : {};
      const phase = Number(idle.phase || 0);
      const swayMs = Math.max(1200, Number(idle.swayMs || 5200));
      const bobMs = Math.max(1200, Number(idle.bobMs || 3600));
      const swayX = Number(idle.swayX || 0);
      const bobY = Number(idle.bobY || 0);
      const restLift = Number(idle.restLift || 0) * Math.max(0.8, state.camera.scale || 1);
      const swayWave = Math.sin(((ts / swayMs) + phase) * Math.PI * 2);
      const bobWave = Math.sin(((ts / bobMs) + (phase * 1.7)) * Math.PI * 2);
      const velocityShiftX = clamp(
        Number(state.ambience.cameraVelocityX || 0) * Number(parallax.factorX || 0),
        -Math.abs(Number(parallax.maxX || 0)),
        Math.abs(Number(parallax.maxX || 0))
      );
      const velocityShiftY = clamp(
        Number(state.ambience.cameraVelocityY || 0) * Number(parallax.factorY || 0),
        -Math.abs(Number(parallax.maxY || 0)),
        Math.abs(Number(parallax.maxY || 0))
      );
      return {
        x: velocityShiftX + (swayWave * swayX),
        y: velocityShiftY - restLift - (((bobWave + 1) * 0.5) * bobY),
        rotateDeg: (swayWave * 1.4) + clamp(velocityShiftX * 0.08, -1.1, 1.1),
        shadowAlpha: clamp(0.32 - (((bobWave + 1) * 0.5) * 0.08), 0.14, 0.34),
        shadowScale: clamp(1 - (((bobWave + 1) * 0.5) * 0.1), 0.86, 1)
      };
    }

    function drawBoardDecorCanvas(ctx, ts = animationNow()) {
      const layers = activeBoardDecorBlueprints();
      if (!layers.length) return;
      ctx.save();
      ctx.setTransform(state.canvas.dpr, 0, 0, state.canvas.dpr, 0, 0);
      for (const blueprint of layers) {
        const rect = plainObject(blueprint?.rect) ? blueprint.rect : null;
        if (!rect) continue;
        const image = canvasImageOrNull(blueprint?.assetSrc);
        if (!image) {
          cacheCanvasImage(blueprint?.assetSrc);
          continue;
        }
        const topLeft = scenePointFromBoardPoint({ x: Number(rect.x || 0), y: Number(rect.y || 0) });
        if (!topLeft) continue;
        const width = Math.max(1, Number(rect.width || image.naturalWidth || 1) * state.camera.scale);
        const height = Math.max(1, Number(rect.height || image.naturalHeight || 1) * state.camera.scale);
        const motion = boardDecorMotion(blueprint, ts);
        const drawX = topLeft.x + motion.x;
        const drawY = topLeft.y + motion.y;
        const offscreenPadding = Math.max(width, height) * 0.22;
        if (
          drawX + width < -offscreenPadding
          || drawY + height < -offscreenPadding
          || drawX > state.canvas.viewportWidth + offscreenPadding
          || drawY > state.canvas.viewportHeight + offscreenPadding
        ) {
          continue;
        }

        const shadowCenterX = drawX + (width * 0.5) + (motion.x * 0.08);
        const shadowCenterY = drawY + height - (10 * Math.max(0.75, state.camera.scale));
        ctx.save();
        ctx.globalAlpha = motion.shadowAlpha;
        ctx.translate(shadowCenterX, shadowCenterY);
        ctx.scale(motion.shadowScale, 0.46 * motion.shadowScale);
        const shadowGradient = ctx.createRadialGradient(0, 0, width * 0.06, 0, 0, width * 0.34);
        shadowGradient.addColorStop(0, "rgba(14, 10, 34, 0.5)");
        shadowGradient.addColorStop(1, "rgba(14, 10, 34, 0)");
        ctx.fillStyle = shadowGradient;
        ctx.beginPath();
        ctx.ellipse(0, 0, width * 0.34, Math.max(8, height * 0.075), 0, 0, Math.PI * 2);
        ctx.fill();
        ctx.restore();

        ctx.save();
        ctx.translate(drawX + (width * 0.5), drawY + (height * 0.54));
        ctx.rotate((motion.rotateDeg * Math.PI) / 180);
        ctx.translate(-(width * 0.5), -(height * 0.54));
        ctx.shadowColor = "rgba(126, 104, 255, 0.34)";
        ctx.shadowBlur = Math.max(8, 16 * Math.max(0.75, state.camera.scale));
        ctx.shadowOffsetX = 0;
        ctx.shadowOffsetY = Math.max(4, 8 * Math.max(0.75, state.camera.scale));
        ctx.drawImage(image, 0, 0, image.naturalWidth || Number(rect.width || 1), image.naturalHeight || Number(rect.height || 1), 0, 0, width, height);
        ctx.restore();
      }
      ctx.restore();
    }

    // MILEAGE_VIEWPORT_RENDER: draw only visible segments and visible entities into the viewport canvas.
    function renderBoardCanvas(ts = animationNow()) {
      if (!boardCtx || !state.board || !state.boardReady) return;
      syncBoardCanvasSize();
      const visibleRect = visibleBoardRect(120);
      state.canvas.lastVisibleRect = visibleRect;
      boardCtx.setTransform(1, 0, 0, 1, 0, 0);
      boardCtx.clearRect(0, 0, boardCanvas.width, boardCanvas.height);
      boardCtx.fillStyle = "#04070f";
      boardCtx.fillRect(0, 0, boardCanvas.width, boardCanvas.height);
      boardCtx.setTransform(
        state.canvas.dpr * state.camera.scale,
        0,
        0,
        state.canvas.dpr * state.camera.scale,
        state.canvas.dpr * state.camera.x,
        state.canvas.dpr * state.camera.y
      );
      updateAmbienceCameraState(ts);
      updateSelfFx(ts);
      drawVisibleSegments(boardCtx, visibleRect);
      drawSelfGroundFxCanvas(boardCtx, ts, visibleRect);
      drawPlayerLayerCanvas(boardCtx, visibleRect);
      drawStepLayerCanvas(boardCtx, visibleRect);
      drawPlayerClusterChipLayerCanvas(boardCtx);
      drawRewardLayerCanvas(boardCtx, visibleRect, ts);
      drawSpriteLayerCanvas(boardCtx, visibleRect, ts);
      drawCloudCapFxCanvas(boardCtx, ts, visibleRect);
      drawAmbientOrbFxCanvas(boardCtx, ts, visibleRect);
      drawSelfParticleFxCanvas(boardCtx, ts);
      drawSelfLayerCanvas(boardCtx, ts);
      boardCtx.setTransform(1, 0, 0, 1, 0, 0);
      drawBoardDecorCanvas(boardCtx, ts);
      drawDebugOverlay(boardCtx);
    }

    function renderBoardTrack() {
      syncBoardCanvasSize();
      boardTrack.innerHTML = "";
      stepLayer.innerHTML = "";
      rewardLayer.innerHTML = "";
      spriteLayer.innerHTML = "";
      playerLayer.innerHTML = "";
      selfLayer.innerHTML = "";
      boardShell.classList.toggle("is-hidden", boardSegments().length === 0);
      rebuildRenderIndex();
    }

    async function waitForBoardImagesReady() {
      const managedEntries = await listManagedMileagePreloads();
      const entries = [
        ...boardSegments().map((segment) => cacheCanvasImage(segment?.src)),
        ...(Array.isArray(state.board?.sprites) ? state.board.sprites.map((sprite) => cacheCanvasImage(sprite?.src)) : []),
        ...(Array.isArray(state.board?.iconTemplates) ? state.board.iconTemplates.map((template) => cacheCanvasImage(template?.src)) : []),
        ...activeBoardDecorBlueprints().map((decor) => cacheCanvasImage(decor?.assetSrc)),
        ...managedEntries.map((entry) => cacheCanvasImage(entry?.originalSrc || entry?.canonicalPath || entry?.url || ""))
      ].filter(Boolean);
      if (entries.length === 0) {
        return Promise.resolve();
      }
      return Promise.all(entries.map((entry) => entry.promise));
    }

    function drawDebugOverlay(ctx) {
      if (!state.debug.enabled || !state.canvas.lastVisibleRect) return;
      const rect = state.canvas.lastVisibleRect;
      const counts = state.canvas.lastVisibleCounts;
      ctx.save();
      ctx.setTransform(state.canvas.dpr, 0, 0, state.canvas.dpr, 0, 0);
      ctx.fillStyle = "rgba(7, 12, 28, 0.84)";
      ctx.strokeStyle = "rgba(143, 245, 255, 0.34)";
      ctx.lineWidth = 1;
      traceRoundedRect(ctx, 14, 14, 270, 86, 16);
      ctx.fill();
      ctx.stroke();
      ctx.fillStyle = "#dff8ff";
      ctx.font = "600 12px var(--font), system-ui, sans-serif";
      ctx.textBaseline = "top";
      ctx.fillText(`debug visible: x ${rect.left.toFixed(0)}..${rect.right.toFixed(0)} y ${rect.top.toFixed(0)}..${rect.bottom.toFixed(0)}`, 26, 28);
      ctx.fillText(`segments ${counts.segments} • steps ${counts.steps} • rewards ${counts.rewards}`, 26, 48);
      ctx.fillText(`sprites ${counts.sprites} • players ${counts.players} • dpr ${state.canvas.dpr.toFixed(2)}`, 26, 68);
      ctx.restore();
    }

    function renderRewardLayer() {
      rewardLayer.innerHTML = "";
    }

    function spriteBackgroundPosition(sprite, frame) {
      const columns = Math.max(1, Number(sprite?.columns || 1));
      const rows = Math.max(1, Number(sprite?.rows || 1));
      const col = frame % columns;
      const row = Math.floor(frame / columns) % rows;
      const x = columns > 1 ? (col / (columns - 1)) * 100 : 0;
      const y = rows > 1 ? (row / (rows - 1)) * 100 : 0;
      return `${x.toFixed(4)}% ${y.toFixed(4)}%`;
    }

    function spriteFrameIndex(sprite, ts = animationNow()) {
      const frameCount = Math.max(1, Number(sprite?.frameCount || 1));
      const fps = Math.max(1, Number(sprite?.fps || 12));
      const frame = Math.floor((ts / 1000) * fps);
      const mode = String(sprite?.mode || "loop");
      if (mode === "static") {
        return Math.max(0, Math.min(frameCount - 1, Math.round(Number(sprite?.frameIndex || 0))));
      }
      if (mode === "once") {
        return Math.min(frameCount - 1, frame);
      }
      if (mode === "pingpong" && frameCount > 1) {
        const cycle = (frameCount * 2) - 2;
        const p = frame % cycle;
        return p < frameCount ? p : cycle - p;
      }
      return frame % frameCount;
    }

    function renderSpriteLayer() {
      spriteLayer.innerHTML = "";
    }

    function updateSpriteLayer(ts = animationNow()) {
      return;
    }

    function renderStepLayer() {
      stepLayer.innerHTML = "";
    }

    function clusterPlayers() {
      const groups = new Map();
      const selfUserId = String(state.bootstrap?.self?.userId || "");
      for (const player of state.bootstrap?.players || []) {
        if (selfUserId && String(player.userId || "") === selfUserId) {
          continue;
        }
        const key = Number(player.positionStep) >= 0 ? String(player.positionStep) : "__entry__";
        if (!groups.has(key)) {
          groups.set(key, []);
        }
        groups.get(key).push(player);
      }
      return groups;
    }

    function renderPlayerLayer() {
      playerLayer.innerHTML = "";
    }

    function debugRewardCountInRange(summary) {
      const reachedStep = Number(summary?.positionStep ?? -1);
      const claimed = new Set(Array.isArray(summary?.claimedRewardIds) ? summary.claimedRewardIds.map((value) => String(value || "")) : []);
      let count = 0;
      for (const reward of Array.isArray(state.board?.rewards) ? state.board.rewards : []) {
        const rewardId = String(reward?.id || "");
        const stepIndex = Number.isInteger(reward?.stepIndex) ? Number(reward.stepIndex) : -1;
        if (rewardId && claimed.has(rewardId)) continue;
        if (stepIndex >= 0 && reachedStep >= stepIndex) {
          count += 1;
        }
      }
      return count;
    }

    function buildDebugPlayers(count, focusStep) {
      const players = [];
      const maxIndex = Math.max(-1, Number(state.board?.steps?.length || 0) - 1);
      if (count <= 0 || maxIndex < 0) return players;
      const spread = Math.max(10, Math.min(48, maxIndex + 1));
      for (let index = 0; index < count; index += 1) {
        let positionStep = ((maxIndex - ((index * 5) % spread)) + spread) % spread;
        if (index % 7 === 0) {
          positionStep = clamp(focusStep, -1, maxIndex);
        }
        players.push({
          userId: `debug-player-${index + 1}`,
          displayName: `Debug ${String.fromCharCode(65 + (index % 26))}${index + 1}`,
          avatarUrl: "",
          positionStep,
          lifetimeSteps: Math.max(0, positionStep + 1),
        });
      }
      return players;
    }

    function applyDebugConfigToBootstrap(inputBootstrap) {
      if (!state.debug.enabled || !inputBootstrap?.board) {
        return inputBootstrap;
      }
      const bootstrap = cloneJson(inputBootstrap);
      const maxIndex = Math.max(-1, Number(bootstrap.board?.steps?.length || 0) - 1);
      const summary = plainObject(bootstrap.summary) ? bootstrap.summary : {};
      const baselineStart = typeof summary.lastAnimatedStep === "number" ? summary.lastAnimatedStep : -1;
      const requestedStart = Number.isFinite(state.debug.start) ? state.debug.start : baselineStart;
      const startStep = clamp(Math.trunc(requestedStart), -1, maxIndex);
      const targetStep = clamp(startStep + Math.max(0, state.debug.steps), -1, maxIndex);
      const pendingSteps = state.debug.walk ? Math.max(0, targetStep - startStep) : 0;
      const claimedRewardIds = Array.isArray(summary.claimedRewardIds) ? summary.claimedRewardIds.slice() : [];

      bootstrap.requiresLogin = false;
      bootstrap.serviceUnavailable = false;
      bootstrap.summary = {
        ...summary,
        boardCode: bootstrap.board?.boardCode || summary.boardCode || initialBoardCode,
        lifetimeSteps: Math.max(Number(summary.lifetimeSteps || 0), targetStep + 1),
        positionStep: targetStep,
        lastAnimatedStep: state.debug.walk ? startStep : targetStep,
        pendingSteps,
        pendingWalkCount: pendingSteps,
        badgeCount: pendingSteps,
        finished: maxIndex >= 0 && targetStep >= maxIndex,
        claimableRewardCount: 0,
        claimedRewardIds,
        requiresLogin: false,
      };
      bootstrap.summary.claimableRewardCount = debugRewardCountInRange(bootstrap.summary);
      bootstrap.progress = {
        lifetimeSteps: bootstrap.summary.lifetimeSteps,
        positionStep: bootstrap.summary.positionStep,
        lastAnimatedStep: bootstrap.summary.lastAnimatedStep,
        finished: bootstrap.summary.finished,
      };
      bootstrap.pending = {
        startStepIndex: pendingSteps > 0 ? startStep + 1 : null,
        endStepIndex: pendingSteps > 0 ? targetStep : null,
        previewRewards: [],
      };
      bootstrap.self = bootstrap.self || {
        userId: "debug-self",
        displayName: "Debug Self",
        avatarUrl: "",
        lifetimeSteps: 0,
        positionStep: -1,
      };
      bootstrap.self.userId = String(bootstrap.self.userId || "debug-self");
      bootstrap.self.displayName = String(bootstrap.self.displayName || "Debug Self");
      bootstrap.self.positionStep = targetStep;
      bootstrap.self.lifetimeSteps = bootstrap.summary.lifetimeSteps;
      if (state.debug.friends > 0) {
        bootstrap.players = buildDebugPlayers(state.debug.friends, targetStep);
      } else if (!Array.isArray(bootstrap.players)) {
        bootstrap.players = [];
      }
      return bootstrap;
    }

    function focusForDebugZone() {
      const zone = state.debug.cityZone;
      if (!zone) return false;
      if (zone === "start" || zone === "lower") {
        const point = boardEntryPoint() || boardPointFromStep(0);
        if (point) {
          setCameraToPoint(point, { scale: state.camera.walkScale, alignY: 0.7, immediate: true });
          return true;
        }
      }
      if (zone === "top") {
        const point = boardPointFromStep(Math.max(0, (state.board?.steps?.length || 1) - 1));
        if (point) {
          setCameraToPoint(point, { scale: state.camera.detailScale, alignY: 0.62, immediate: true });
          return true;
        }
      }
      return false;
    }

    function leaderboardRows() {
      const leaderboard = state.bootstrap?.leaderboard || {};
      const tab = state.leaderboardTab === "weekly" ? "weekly" : "all";
      return Array.isArray(leaderboard[tab]) ? leaderboard[tab] : [];
    }

    function selfUserId() {
      return String(state.bootstrap?.self?.userId || "").trim();
    }

    function isSelfPlayer(player) {
      const target = selfUserId();
      return target !== "" && String(player?.userId || "").trim() === target;
    }

    function leaderboardRowsForDisplay(rows) {
      const list = Array.isArray(rows) ? rows : [];
      const target = selfUserId();
      if (!target || list.length <= 50) {
        return list.map((player) => ({ type: "player", player }));
      }
      const selfIndex = list.findIndex((player) => String(player?.userId || "").trim() === target);
      if (selfIndex < 0 || selfIndex < 50) {
        return list.slice(0, 50).map((player) => ({ type: "player", player }));
      }
      return [
        ...list.slice(0, 50).map((player) => ({ type: "player", player })),
        { type: "divider" },
        { type: "player", player: list[selfIndex] }
      ];
    }

    function findPlayerByUserId(userId) {
      const target = String(userId || "");
      if (!target) return null;
      const self = state.bootstrap?.self || null;
      if (self && String(self.userId || "") === target) return self;
      return (state.bootstrap?.players || []).find((player) => String(player.userId || "") === target)
        || leaderboardRows().find((player) => String(player.userId || "") === target)
        || (state.stepPlayersPanel.players || []).find((player) => String(player.userId || "") === target)
        || null;
    }

    function focusOnPlayer(player, smooth = true) {
      const point = pointForPlayer(player);
      if (!point) return;
      const options = {
        scale: state.camera.detailScale,
        alignY: 0.66,
        duration: 650
      };
      if (smooth) {
        animateCameraToPoint(point, options);
      } else {
        setCameraToPoint(point, options);
      }
    }

    function renderLeaderboard() {
      const rows = leaderboardRows();
      const displayRows = leaderboardRowsForDisplay(rows);
      const tab = state.leaderboardTab === "weekly" ? "weekly" : "all";
      if (leaderboardTitle) {
        leaderboardTitle.textContent = tab === "weekly" ? "Rank สัปดาห์" : "Rank ทั้งหมด";
      }
      if (leaderboardCount) {
        leaderboardCount.textContent = `${rows.length.toLocaleString()} คน`;
      }
      document.querySelectorAll("[data-rank-tab]").forEach((button) => {
        button.classList.toggle("is-active", button.dataset.rankTab === tab);
      });
      if (!leaderboardList) return;
      if (rows.length === 0) {
        leaderboardList.innerHTML = `<div class="leaderboard-empty">ยังไม่มีคนเดินอย่างน้อย 1 ช่อง</div>`;
        return;
      }
      leaderboardList.innerHTML = displayRows.map((entry, index) => {
        if (entry.type === "divider") {
          return `<div class="leaderboard-divider">อันดับของคุณ</div>`;
        }
        const player = entry.player;
        const avatarUrl = String(player.avatarUrl || "");
        const displayName = String(player.displayName || "Player");
        const score = Number(tab === "weekly" ? player.weeklySteps : player.lifetimeSteps) || Number(player.score || 0);
        const stepText = Number(player.positionStep || -1) >= 0 ? `ช่อง ${stepDisplayNumber(player.positionStep)}` : "ยังอยู่จุดเริ่ม";
        const selfClass = isSelfPlayer(player) ? " is-self" : "";
        return `
          <button class="leaderboard-row${selfClass}" type="button" data-rank-user-id="${escapeHtml(player.userId || "")}">
            <span class="leaderboard-rank">#${Number(player.rank || index + 1)}</span>
            <span class="leaderboard-avatar">${avatarUrl ? `<img src="${escapeHtml(avatarUrl)}" alt="" loading="lazy" decoding="async" />` : escapeHtml(playerInitial(player))}</span>
            <span class="leaderboard-name">
              <strong>${escapeHtml(displayName)}</strong>
              <small>${escapeHtml(stepText)}</small>
            </span>
            <span class="leaderboard-score">${score.toLocaleString()}</span>
          </button>
        `;
      }).join("");
    }

    function scrollLeaderboardToSelf() {
      if (!leaderboardList) return;
      const selfRow = leaderboardList.querySelector(".leaderboard-row.is-self");
      if (!selfRow) return;
      window.setTimeout(() => {
        selfRow.scrollIntoView({ block: "center", inline: "nearest" });
      }, 60);
    }

    async function refreshLeaderboard() {
      try {
        const data = await fetchMileageJson("leaderboard");
        if (data?.leaderboard) {
          state.bootstrap.leaderboard = data.leaderboard;
          renderLeaderboard();
          scrollLeaderboardToSelf();
        }
      } catch (error) {
        console.warn("Mileage leaderboard failed:", error);
      }
    }

    function openLeaderboard() {
      state.leaderboardOpen = true;
      closeStepPlayersPanel(false);
      leaderboardPanel.classList.remove("is-hidden");
      leaderboardPanel.setAttribute("aria-hidden", "false");
      syncMileageOverlayChrome();
      renderLeaderboard();
      scrollLeaderboardToSelf();
      postMileageUiStateToParent();
      void refreshLeaderboard();
    }

    function closeLeaderboard(postState = true) {
      state.leaderboardOpen = false;
      leaderboardPanel.classList.add("is-hidden");
      leaderboardPanel.setAttribute("aria-hidden", "true");
      syncMileageOverlayChrome();
      if (postState) {
        postMileageUiStateToParent();
      }
    }

    function renderStepPlayersPanel() {
      if (!stepPlayersPanel || !stepPlayersList || !stepPlayersTitle || !stepPlayersCount) return;
      const stepIndex = Number(state.stepPlayersPanel.stepIndex ?? -1);
      const players = Array.isArray(state.stepPlayersPanel.players) ? state.stepPlayersPanel.players : [];
      stepPlayersTitle.textContent = stepIndex >= 0 ? `ช่อง ${stepDisplayNumber(stepIndex)}` : "ผู้เล่นในช่อง";
      stepPlayersCount.textContent = state.stepPlayersPanel.loading
        ? "กำลังโหลด..."
        : `${players.length.toLocaleString()} คน`;
      if (state.stepPlayersPanel.loading) {
        stepPlayersList.innerHTML = `<div class="leaderboard-empty">กำลังโหลดรายชื่อ</div>`;
        return;
      }
      if (players.length === 0) {
        stepPlayersList.innerHTML = `<div class="leaderboard-empty">ยังไม่เจอรายชื่อในช่องนี้</div>`;
        return;
      }
      stepPlayersList.innerHTML = players.map((player) => {
        const avatarUrl = String(player.avatarUrl || "");
        const displayName = String(player.displayName || "Player");
        const score = Number(player.lifetimeSteps || player.score || 0);
        const selfClass = isSelfPlayer(player) ? " is-self" : "";
        return `
          <button class="leaderboard-row${selfClass}" type="button" data-step-user-id="${escapeHtml(player.userId || "")}">
            <span class="leaderboard-rank">#</span>
            <span class="leaderboard-avatar">${avatarUrl ? `<img src="${escapeHtml(avatarUrl)}" alt="" loading="lazy" decoding="async" />` : escapeHtml(playerInitial(player))}</span>
            <span class="leaderboard-name">
              <strong>${escapeHtml(displayName)}</strong>
              <small>${escapeHtml(score > 0 ? `${score.toLocaleString()} ช่องสะสม` : "อยู่จุดเริ่ม")}</small>
            </span>
            <span class="leaderboard-score">ดู</span>
          </button>
        `;
      }).join("");
    }

    function debugPlayersForStep(stepIndex) {
      return (state.bootstrap?.players || [])
        .filter((player) => Number(player?.positionStep ?? -1) === Number(stepIndex));
    }

    async function openStepPlayersPanel(stepIndex) {
      const targetStep = Number(stepIndex);
      if (!Number.isInteger(targetStep) || targetStep < 0) return;
      closeLeaderboard(false);
      state.stepPlayersPanel.open = true;
      state.stepPlayersPanel.stepIndex = targetStep;
      state.stepPlayersPanel.loading = true;
      state.stepPlayersPanel.players = state.debug.enabled ? debugPlayersForStep(targetStep) : [];
      stepPlayersPanel.classList.remove("is-hidden");
      stepPlayersPanel.setAttribute("aria-hidden", "false");
      syncMileageOverlayChrome();
      renderStepPlayersPanel();
      postMileageUiStateToParent();
      if (state.debug.enabled) {
        state.stepPlayersPanel.loading = false;
        renderStepPlayersPanel();
        return;
      }
      try {
        const data = await fetchMileageJson("step_players", { stepIndex: targetStep });
        state.stepPlayersPanel.players = Array.isArray(data.players) ? data.players : [];
      } catch (error) {
        console.warn("Mileage step players failed:", error);
        state.stepPlayersPanel.players = [];
        showToast("โหลดรายชื่อช่องนี้ไม่สำเร็จ");
      } finally {
        state.stepPlayersPanel.loading = false;
        renderStepPlayersPanel();
      }
    }

    function closeStepPlayersPanel(postState = true) {
      state.stepPlayersPanel.open = false;
      if (stepPlayersPanel) {
        stepPlayersPanel.classList.add("is-hidden");
        stepPlayersPanel.setAttribute("aria-hidden", "true");
      }
      syncMileageOverlayChrome();
      if (postState) {
        postMileageUiStateToParent();
      }
    }

    function renderSelfLayer(ts = animationNow()) {
      selfLayer.innerHTML = "";
    }

    function rewardAtViewportClient(clientX, clientY) {
      const rewards = Array.isArray(state.board?.rewards) ? state.board.rewards : [];
      if (!rewards.length) return null;
      const boardPoint = viewportPointToBoard(clientX, clientY);
      let best = null;
      for (const reward of rewards) {
        const point = rewardPoint(reward);
        if (!point) continue;
        const radius = rewardMarkerSize(reward) * 0.58;
        const dx = boardPoint.x - point.x;
        const dy = boardPoint.y - point.y;
        const distanceSq = (dx * dx) + (dy * dy);
        if (distanceSq > radius * radius) continue;
        if (!best || distanceSq < best.distanceSq) {
          best = { reward, point, distanceSq };
        }
      }
      return best;
    }

    function playerClusterAtViewportClient(clientX, clientY) {
      const boardPoint = viewportPointToBoard(clientX, clientY);
      let best = null;
      for (const target of state.canvas.playerClusterTargets || []) {
        const dx = boardPoint.x - Number(target.x || 0);
        const dy = boardPoint.y - Number(target.y || 0);
        const radius = Math.max(16, Number(target.radius || 18));
        const distanceSq = (dx * dx) + (dy * dy);
        if (distanceSq > radius * radius) continue;
        if (!best || distanceSq < best.distanceSq) {
          best = { ...target, distanceSq };
        }
      }
      return best;
    }

    function activatePlayerClusterDetail(clientX, clientY) {
      const hit = playerClusterAtViewportClient(clientX, clientY);
      if (!hit) return false;
      void openStepPlayersPanel(Number(hit.stepIndex));
      return true;
    }

    function activateCanvasReward(clientX, clientY) {
      const hit = rewardAtViewportClient(clientX, clientY);
      if (!hit) return false;
      if (state.bootstrap?.requiresLogin) {
        showToast("Sign in Discord ก่อนเพื่อรับรางวัล");
        syncLoginButton(true);
        return true;
      }
      const rewardId = String(hit.reward.id || "").trim();
      const stepIndex = Number.isInteger(hit.reward.stepIndex) ? Number(hit.reward.stepIndex) : -1;
      const reachedStep = Number(state.bootstrap?.summary?.positionStep ?? -1);
      const claimed = rewardId !== "" && state.claimedRewardIds.has(rewardId);
      if (claimed) {
        showToast("รับรางวัลจุดนี้แล้ว");
        return true;
      }
      if (!Number.isFinite(stepIndex) || stepIndex < 0 || reachedStep < stepIndex) {
        showToast("เดินถึงช่องนี้ก่อน แล้วค่อยกลับมากดรับ");
        return true;
      }
      if (!state.walk.pendingClaim) {
        state.walk.pendingClaim = true;
        void claimPendingRewards({
          quietEmpty: false,
          sourcePoint: viewportPointFromBoardPoint(hit.point)
        });
      }
      return true;
    }

    function resetWalkPose() {
      state.walk.visualLiftPx = markerStyle.selfLift;
      state.walk.visualScaleX = 1;
      state.walk.visualScaleY = 1;
      state.walk.visualTiltDeg = 0;
      state.walk.visualOffsetX = 0;
      state.walk.visualOffsetY = 0;
    }

    function walkRemainingSteps(fromValue = state.walk.segmentFromValue) {
      return Math.max(1, Math.ceil(state.walk.toValue - fromValue));
    }

    function summaryPendingWalkSteps(summary = state.bootstrap?.summary) {
      return Math.max(0, Number(summary?.pendingSteps || 0));
    }

    function canAnimatePendingWalk(summary = state.bootstrap?.summary) {
      const targetValue = Number(summary?.positionStep ?? -1);
      return summaryPendingWalkSteps(summary) > 0 && Number.isFinite(targetValue) && targetValue >= 0;
    }

    function walkDisplayRemainingSteps() {
      if (state.walk.active) {
        return Math.max(0, Math.ceil(state.walk.toValue - state.walk.currentValue));
      }
      return summaryPendingWalkSteps();
    }

    function walkSegmentPoints(fromValue, toValue) {
      const fromPoint = pathPointForValue(fromValue);
      const toPoint = pathPointForValue(toValue);
      const dx = Number(toPoint?.x || 0) - Number(fromPoint?.x || 0);
      const dy = Number(toPoint?.y || 0) - Number(fromPoint?.y || 0);
      const distance = Math.max(1, Math.hypot(dx, dy));
      return {
        fromPoint,
        toPoint,
        dx,
        dy,
        distance,
        tangentX: dx / distance,
        tangentY: dy / distance,
        normalX: -dy / distance,
        normalY: dx / distance,
        tiltDirection: dx === 0 ? 1 : Math.sign(dx)
      };
    }

    function walkSegmentDurationMs(fromValue) {
      const nextValue = Math.min(state.walk.toValue, fromValue + 1);
      const { distance } = walkSegmentPoints(fromValue, nextValue);
      const distanceT = clamp((distance - 24) / 72, 0, 1);
      const duration = state.walk.boost
        ? lerp(150, 230, distanceT)
        : lerp(500, 620, distanceT);
      const adjusted = fromValue < 0 && !state.walk.boost ? duration + 44 : duration;
      const previewSpeed = state.preview.enabled
        ? clamp(Number(state.preview.simulation?.speed || 1), 0.25, 3)
        : 1;
      return adjusted / previewSpeed;
    }

    function walkSegmentHopHeight(fromValue, toValue) {
      const { fromPoint, toPoint, distance } = walkSegmentPoints(fromValue, toValue);
      if (!fromPoint || !toPoint) {
        return state.walk.boost ? 22 : 28;
      }
      const height = clamp(distance * 0.34, 28, 52);
      return state.walk.boost ? height * 0.78 : height;
    }

    function walkSegmentTiltDirection(fromValue, toValue) {
      const fromPoint = pathPointForValue(fromValue);
      const toPoint = pathPointForValue(toValue);
      const deltaX = Number(toPoint?.x || 0) - Number(fromPoint?.x || 0);
      return deltaX === 0 ? 1 : Math.sign(deltaX);
    }

    function samplePremiumWalkSegment(progress, fromValue, toValue) {
      const p = clamp(progress, 0, 1);
      const kinematics = walkSegmentPoints(fromValue, toValue);
      const windupEnd = 0.13;
      const moveRaw = clamp((p - 0.09) / 0.78, 0, 1);
      const movePhase = moveRaw < 0.42
        ? 0.68 * easeOutQuart(moveRaw / 0.42)
        : 0.68 + (0.32 * easeInOutSine((moveRaw - 0.42) / 0.58));
      const hopHeight = walkSegmentHopHeight(fromValue, toValue);
      const hop = Math.sin(movePhase * Math.PI);
      const windup = p < windupEnd ? easeOutCubic(p / windupEnd) : 0;
      const launchT = p > 0.09 && p < 0.32 ? (p - 0.09) / 0.23 : 0;
      const launchStretch = launchT > 0 ? Math.sin(clamp(launchT, 0, 1) * Math.PI) : 0;
      const landingT = p > 0.73 && p < 0.92 ? (p - 0.73) / 0.19 : 0;
      const landingSquash = landingT > 0 ? Math.sin(clamp(landingT, 0, 1) * Math.PI) : 0;
      const settleT = p > 0.86 ? (p - 0.86) / 0.14 : 0;
      const settle = settleT > 0
        ? Math.exp(-settleT * 4.6) * Math.sin(settleT * Math.PI * 2.45)
        : 0;
      const sideSway = Math.sin(movePhase * Math.PI * 2) * hop * 1.25;
      const pullBack = windup * (state.walk.boost ? 2.6 : 4.8);
      const forwardSnap = launchStretch * (state.walk.boost ? 1.6 : 2.4);

      return {
        currentValue: lerp(fromValue, toValue, movePhase),
        liftPx: markerStyle.selfLift - (windup * 2.2) + (hopHeight * hop) + (hopHeight * Math.max(0, settle) * 0.08),
        scaleX: 1 + (windup * 0.16) - (launchStretch * 0.055) - (hop * 0.045) + (landingSquash * 0.19) - (settle * 0.025),
        scaleY: 1 - (windup * 0.13) + (launchStretch * 0.13) + (hop * 0.09) - (landingSquash * 0.18) + (settle * 0.045),
        tiltDeg: kinematics.tiltDirection * ((launchStretch * 8.4) + (hop * 5.2) - (windup * 4.6) - (landingSquash * 5.4) + (settle * 2.8)),
        offsetX: (-kinematics.tangentX * pullBack) + (kinematics.tangentX * forwardSnap) + (kinematics.normalX * sideSway),
        offsetY: (-kinematics.tangentY * pullBack) + (kinematics.tangentY * forwardSnap) + (kinematics.normalY * sideSway)
      };
    }

    function setWalkSegment(fromValue, ts = animationNow()) {
      const nextValue = Math.min(state.walk.toValue, fromValue + 1);
      if (nextValue <= fromValue) {
        finishWalkAndClaim();
        return;
      }
      state.walk.segmentFromValue = fromValue;
      state.walk.segmentToValue = nextValue;
      state.walk.segmentStartAt = ts;
      state.walk.segmentDurationMs = walkSegmentDurationMs(fromValue);
      state.walk.currentValue = fromValue;
      resetWalkPose();
    }

    function updateBoostButton() {
      if (!boostButton) return;
      const visible = state.walk.active;
      boostButton.classList.toggle("is-hidden", !visible);
      boostButton.disabled = !visible;
      boostButton.textContent = ">>";
      boostButton.classList.toggle("is-boosting", Boolean(state.walk.boost));
      boostButton.setAttribute("aria-pressed", state.walk.boost ? "true" : "false");
    }

    function updateWalkActionButton() {
      if (!walkActionButton || !walkActionTitle || !walkActionCount) return;

      const animatable = canAnimatePendingWalk();
      const remainingSteps = walkDisplayRemainingSteps();
      let visible = false;
      let disabled = false;
      let titleText = "เริ่มเดิน";
      let countText = `เหลือ ${remainingSteps.toLocaleString()} ก้าว`;

      if (state.bootstrap?.requiresLogin) {
        visible = false;
      } else if (state.walk.pendingClaim) {
        visible = true;
        disabled = true;
        titleText = "กำลังรับรางวัล...";
        countText = "กำลังอัปเดตข้อมูล";
      } else if (state.walk.active) {
        visible = true;
        disabled = true;
        titleText = "กำลังเดิน";
        countText = `เหลือ ${remainingSteps.toLocaleString()} ก้าว`;
      } else if (animatable) {
        visible = true;
      }

      walkActionButton.classList.toggle("is-hidden", !visible);
      walkActionButton.disabled = disabled;
      walkActionButton.setAttribute("aria-hidden", visible ? "false" : "true");
      if (walkActionButton.dataset.state !== titleText) {
        walkActionButton.dataset.state = titleText;
      }
      if (walkActionTitle.textContent !== titleText) {
        walkActionTitle.textContent = titleText;
      }
      if (walkActionCount.textContent !== countText) {
        walkActionCount.textContent = countText;
      }
    }

    function setWalkBoost(active) {
      const enabled = Boolean(active);
      if (!state.walk.active || state.walk.boost === enabled) {
        updateBoostButton();
        return;
      }
      const now = animationNow();
      const elapsed = now - state.walk.segmentStartAt;
      const progress = state.walk.segmentDurationMs > 0
        ? clamp(elapsed / state.walk.segmentDurationMs, 0, 1)
        : 0;
      state.walk.boost = enabled;
      state.walk.segmentDurationMs = walkSegmentDurationMs(state.walk.segmentFromValue);
      state.walk.segmentStartAt = now - (progress * state.walk.segmentDurationMs);
      updateBoostButton();
    }

    async function claimPendingRewards(options = {}) {
      try {
        const pickupSourcePoint = rewardPickupSourcePoint(options);
        const data = await fetchMileageJson("claim_pending");
        const claimedRewards = Array.isArray(data.claimedRewards) ? data.claimedRewards : [];
        state.bootstrap.summary = data.summary;
        syncClaimedRewardState(data.summary);
        markRecentlyClaimedRewards(claimedRewards);
        updateSummary();
        renderRewardLayer();
        updateWalkActionButton();
        if (claimedRewards.length > 0) {
          playLocalRewardPickup(claimedRewards, { sourcePoint: pickupSourcePoint });
          showToast(rewardClaimToastText(claimedRewards) || `รับรางวัลอัตโนมัติ ${claimedRewards.length} จุดแล้ว`, 2800);
        } else if (!options.quietEmpty) {
          showToast("ยังไม่มีรางวัลใหม่ที่รับได้");
        }
        postSummaryToParent({
          balancesAfter: rewardClaimBalancesAfter(claimedRewards),
          claimedRewards,
          sourcePoint: pickupSourcePoint
        });
      } catch (error) {
        console.warn("Mileage claim failed:", error);
        showToast("รับรางวัลไม่สำเร็จ ลองเปิดหน้านี้อีกครั้ง");
      } finally {
        state.walk.pendingClaim = false;
        updateWalkActionButton();
      }
    }

    function finishWalkAndClaim() {
      state.walk.active = false;
      state.walk.currentValue = state.walk.toValue;
      state.walk.segmentFromValue = state.walk.toValue;
      state.walk.segmentToValue = state.walk.toValue;
      state.walk.segmentDurationMs = 0;
      state.walk.boost = false;
      resetWalkPose();
      updateBoostButton();
      renderSelfLayer();
      if (!state.walk.pendingClaim) {
        state.walk.pendingClaim = true;
        updateWalkActionButton();
        void claimPendingRewards({ quietEmpty: true });
      }
    }

    function startPendingWalk() {
      if (state.walk.active || state.walk.pendingClaim) {
        updateWalkActionButton();
        return;
      }
      if (state.bootstrap?.requiresLogin) {
        showToast("Sign in Discord ก่อน แล้วค่อยเริ่มเดิน");
        syncLoginButton(true);
        updateWalkActionButton();
        return;
      }
      const summary = state.bootstrap?.summary;
      const pendingSteps = Number(summary?.pendingSteps ?? 0);
      const targetValue = Number(summary?.positionStep ?? -1);
      if (!summary || pendingSteps <= 0 || !Number.isFinite(targetValue) || targetValue < 0) {
        updateBoostButton();
        updateWalkActionButton();
        focusOnSelf(true);
        return;
      }

      const lastAnimatedValue = Number(summary.lastAnimatedStep ?? -1);
      const fromValue = Number.isFinite(lastAnimatedValue) ? lastAnimatedValue : -1;
      const toValue = targetValue;

      state.walk.active = true;
      state.walk.pendingClaim = false;
      state.walk.fromValue = fromValue;
      state.walk.toValue = toValue;
      state.walk.currentValue = fromValue;
      state.walk.startAt = animationNow();
      state.walk.boost = false;
      state.walk.restoreScale = clamp(state.camera.scale, state.camera.minScale, state.camera.maxScale);
      resetWalkPose();
      setWalkSegment(fromValue, state.walk.startAt);
      resetSelfFxTrail(state.walk.startAt);
      if (Number(state.gesture.manualOverrideUntil || 0) <= state.walk.startAt) {
        focusOnWalkArea(true, true);
      }

      updateBoostButton();
      updateWalkActionButton();
      renderSelfLayer();
    }

    function updateWalk(ts) {
      if (!state.walk.active) return;

      const duration = Math.max(1, state.walk.segmentDurationMs);
      const elapsed = ts - state.walk.segmentStartAt;
      const t = clamp(elapsed / duration, 0, 1);
      const motion = samplePremiumWalkSegment(t, state.walk.segmentFromValue, state.walk.segmentToValue);
      state.walk.currentValue = motion.currentValue;
      state.walk.visualLiftPx = motion.liftPx;
      state.walk.visualScaleX = motion.scaleX;
      state.walk.visualScaleY = motion.scaleY;
      state.walk.visualTiltDeg = motion.tiltDeg;
      state.walk.visualOffsetX = motion.offsetX;
      state.walk.visualOffsetY = motion.offsetY;
      updateWalkCameraFollow(ts);

      if (t >= 1) {
        state.walk.currentValue = state.walk.segmentToValue;
        state.walk.visualOffsetX = 0;
        state.walk.visualOffsetY = 0;
        addLandingStepPulse(state.walk.segmentToValue, ts);
        if (state.walk.segmentToValue >= state.walk.toValue) {
          finishWalkAndClaim();
          return;
        }
        setWalkSegment(state.walk.segmentToValue, ts);
      }
    }

    function animationLoop(ts) {
      const timelineTs = ts - state.time.pausedDuration;
      updateCameraMomentum(timelineTs);
      updateCameraTween(timelineTs);
      updateWalk(timelineTs);
      updateSpriteLayer(timelineTs);
      renderBoardCanvas(timelineTs);
      updateWalkActionButton();
      state.animationFrame = window.requestAnimationFrame(animationLoop);
    }

    function resetGestureState() {
      state.gesture.pointers.clear();
      state.gesture.dragStart = null;
      state.gesture.pinchStart = null;
      state.gesture.velocityX = 0;
      state.gesture.velocityY = 0;
      state.gesture.assistStrength = 0;
      state.gesture.pathLock = false;
      scene.classList.remove("is-dragging");
    }

    function pauseAnimationState() {
      if (!state.time.pauseStartedAt) {
        state.time.pauseStartedAt = performance.now();
      }
      resetGestureState();
      stopCameraMotion();
      setWalkBoost(false);
    }

    function resumeAnimationState() {
      if (state.time.pauseStartedAt) {
        state.time.pausedDuration += performance.now() - state.time.pauseStartedAt;
        state.time.pauseStartedAt = 0;
      }
      resetGestureState();
    }

    function gestureDistance(a, b) {
      return Math.hypot(a.clientX - b.clientX, a.clientY - b.clientY);
    }

    function gestureMidpoint(a, b) {
      const rect = sceneRect();
      return {
        x: ((a.clientX + b.clientX) / 2) - rect.left,
        y: ((a.clientY + b.clientY) / 2) - rect.top
      };
    }

    function updateGestureMode() {
      const pointers = Array.from(state.gesture.pointers.values());
      if (pointers.length >= 2) {
        const [a, b] = pointers;
        const midpoint = gestureMidpoint(a, b);
        state.gesture.pinchStart = {
          distance: Math.max(1, gestureDistance(a, b)),
          scale: state.camera.scale,
          midpoint,
          anchor: {
            x: (midpoint.x - state.camera.x) / state.camera.scale,
            y: (midpoint.y - state.camera.y) / state.camera.scale
          }
        };
        state.gesture.dragStart = null;
        state.gesture.velocityX = 0;
        state.gesture.velocityY = 0;
        state.gesture.pathLock = false;
        return;
      }
      if (pointers.length === 1) {
        const [p] = pointers;
        state.gesture.dragStart = {
          pointerId: p.pointerId,
          clientX: p.clientX,
          clientY: p.clientY,
          cameraX: state.camera.x,
          cameraY: state.camera.y
        };
        state.gesture.lastMoveX = p.clientX;
        state.gesture.lastMoveY = p.clientY;
        state.gesture.lastMoveAt = performance.now();
        state.gesture.velocityX = 0;
        state.gesture.velocityY = 0;
        state.gesture.assistStrength = 0;
        state.gesture.pathLock = false;
        state.gesture.pinchStart = null;
        return;
      }
      state.gesture.dragStart = null;
      state.gesture.pinchStart = null;
      state.gesture.assistStrength = 0;
      state.gesture.pathLock = false;
    }

    function onPointerDown(event) {
      if (event.target === zoomSlider) return;
      if (event.target.closest(".reward-marker")) return;
      event.preventDefault();
      stopCameraMotion();
      state.gesture.manualOverrideUntil = animationNow() + 1500;
      scene.setPointerCapture?.(event.pointerId);
      state.gesture.pointers.set(event.pointerId, event);
      scene.classList.add("is-dragging");
      updateGestureMode();
    }

    function onPointerMove(event) {
      if (!state.gesture.pointers.has(event.pointerId)) return;
      event.preventDefault();
      state.gesture.pointers.set(event.pointerId, event);
      const pointers = Array.from(state.gesture.pointers.values());
      if (pointers.length >= 2 && state.gesture.pinchStart) {
        const [a, b] = pointers;
        const midpoint = gestureMidpoint(a, b);
        const distance = Math.max(1, gestureDistance(a, b));
        const nextScale = clamp(
          state.gesture.pinchStart.scale * (distance / state.gesture.pinchStart.distance),
          state.camera.minScale,
          state.camera.maxScale
        );
        state.camera.scale = nextScale;
        state.camera.x = midpoint.x - state.gesture.pinchStart.anchor.x * nextScale;
        state.camera.y = midpoint.y - state.gesture.pinchStart.anchor.y * nextScale;
        applyCamera();
        return;
      }
      if (pointers.length === 1 && state.gesture.dragStart) {
        const now = performance.now();
        const dt = Math.max(0.001, (now - (state.gesture.lastMoveAt || now)) / 1000);
        state.gesture.velocityX = (event.clientX - state.gesture.lastMoveX) / dt;
        state.gesture.velocityY = (event.clientY - state.gesture.lastMoveY) / dt;
        state.gesture.lastMoveX = event.clientX;
        state.gesture.lastMoveY = event.clientY;
        state.gesture.lastMoveAt = now;
        state.gesture.pathLock = false;
        state.gesture.assistStrength = 0;
        state.camera.x = state.gesture.dragStart.cameraX + event.clientX - state.gesture.dragStart.clientX;
        state.camera.y = state.gesture.dragStart.cameraY + event.clientY - state.gesture.dragStart.clientY;
        applyCamera();
      }
    }

    function onPointerEnd(event) {
      if (state.gesture.pointers.has(event.pointerId)) {
        state.gesture.pointers.delete(event.pointerId);
      }
      scene.releasePointerCapture?.(event.pointerId);
      if (state.gesture.pointers.size === 0) {
        scene.classList.remove("is-dragging");
        const speed = Math.hypot(state.gesture.velocityX, state.gesture.velocityY);
        if (speed > 80) {
          state.cameraMotion.active = true;
          state.cameraMotion.vx = state.gesture.velocityX;
          state.cameraMotion.vy = state.gesture.velocityY;
          state.cameraMotion.followPathX = state.gesture.pathLock;
          state.cameraMotion.lockPathY = state.gesture.pathLock;
          state.cameraMotion.followStrength = state.gesture.pathLock
            ? clamp(Math.max(0.16, state.gesture.assistStrength * 0.62), 0.16, 0.28)
            : 0;
          state.cameraMotion.verticalStrength = state.gesture.pathLock
            ? clamp(Math.max(0.12, state.gesture.assistStrength * 0.34), 0.12, 0.22)
            : 0;
          state.cameraMotion.alignY = state.gesture.pathLock ? 0.5 : 0.58;
          state.cameraMotion.snapToStep = state.gesture.pathLock;
          state.cameraMotion.lastAt = animationNow();
        }
      }
      if (event.type === "pointercancel") {
        resetGestureState();
      }
      updateGestureMode();
    }

    function preventViewportGesture(event) {
      event.preventDefault();
    }

    scene.addEventListener("pointerdown", onPointerDown, { passive: false });
    scene.addEventListener("pointermove", onPointerMove, { passive: false });
    scene.addEventListener("pointerup", onPointerEnd, { passive: false });
    scene.addEventListener("pointercancel", onPointerEnd, { passive: false });
    scene.addEventListener("wheel", (event) => {
      event.preventDefault();
      if (event.ctrlKey || event.metaKey || event.altKey) {
        const rect = sceneRect();
        const anchor = {
          x: event.clientX - rect.left,
          y: event.clientY - rect.top
        };
        const primaryDelta = Math.abs(event.deltaY) >= Math.abs(event.deltaX) ? event.deltaY : event.deltaX;
        const factor = Math.exp(-primaryDelta * 0.0012);
        setCameraScale(state.camera.scale * factor, anchor);
        return;
      }
      const followStrength = pathAssistStrengthFromTravel(event.deltaX, event.deltaY);
      state.cameraTween.active = false;
      state.gesture.manualOverrideUntil = animationNow() + 1500;
      state.camera.x -= event.deltaX;
      state.camera.y -= event.deltaY;
      state.cameraMotion.active = true;
      state.cameraMotion.vx = -event.deltaX * 18;
      state.cameraMotion.vy = -event.deltaY * 18;
      state.cameraMotion.followPathX = false;
      state.cameraMotion.lockPathY = false;
      state.cameraMotion.followStrength = 0;
      state.cameraMotion.verticalStrength = 0;
      state.cameraMotion.alignY = 0.58;
      state.cameraMotion.snapToStep = false;
      state.cameraMotion.lastAt = animationNow();
      applyCamera();
    }, { passive: false });

    document.addEventListener("gesturestart", preventViewportGesture, { passive: false });
    document.addEventListener("gesturechange", preventViewportGesture, { passive: false });
    document.addEventListener("gestureend", preventViewportGesture, { passive: false });
    document.addEventListener("touchmove", preventViewportGesture, { passive: false });
    window.addEventListener("pagehide", () => {
      postMileageUiStateToParent({ overlayOpen: false });
      pauseAnimationState();
    }, { passive: true });
    document.addEventListener("visibilitychange", () => {
      if (document.hidden) {
        pauseAnimationState();
      } else {
        resumeAnimationState();
      }
    });

    zoomSlider.addEventListener("input", () => {
      setCameraScale(scaleFromZoomSlider(), viewportCenter());
    });

    rewardLayer.addEventListener("pointerdown", (event) => {
      event.stopPropagation();
    });

    rewardLayer.addEventListener("click", (event) => {
      event.preventDefault();
      activateCanvasReward(event.clientX, event.clientY);
    });

    scene.addEventListener("click", (event) => {
      if (activatePlayerClusterDetail(event.clientX, event.clientY) || activateCanvasReward(event.clientX, event.clientY)) {
        event.preventDefault();
      }
    });

    selfButton.addEventListener("click", () => {
      focusOnSelf(true, true);
    });

    rankButton.addEventListener("click", () => {
      if (state.leaderboardOpen) {
        closeLeaderboard();
      } else {
        openLeaderboard();
      }
    });

    leaderboardClose.addEventListener("click", closeLeaderboard);
    leaderboardPanel.addEventListener("click", (event) => {
      if (event.target === leaderboardPanel) {
        closeLeaderboard();
        return;
      }
      const tabButton = event.target.closest("[data-rank-tab]");
      if (tabButton) {
        state.leaderboardTab = tabButton.dataset.rankTab === "weekly" ? "weekly" : "all";
        renderLeaderboard();
        return;
      }
      const row = event.target.closest("[data-rank-user-id]");
      if (!row) return;
      const player = findPlayerByUserId(row.dataset.rankUserId || "");
      if (player) {
        closeLeaderboard();
        focusOnPlayer(player, true);
      }
    });

    stepPlayersClose.addEventListener("click", () => closeStepPlayersPanel());
    stepPlayersPanel.addEventListener("click", (event) => {
      if (event.target === stepPlayersPanel) {
        closeStepPlayersPanel();
        return;
      }
      const row = event.target.closest("[data-step-user-id]");
      if (!row) return;
      const player = findPlayerByUserId(row.dataset.stepUserId || "");
      if (player) {
        closeStepPlayersPanel();
        focusOnPlayer(player, true);
      }
    });

    function beginBoostHold(event) {
      event.preventDefault();
      boostButton.setPointerCapture?.(event.pointerId);
      setWalkBoost(true);
    }

    function endBoostHold(event) {
      event?.preventDefault?.();
      if (event?.pointerId !== undefined) {
        boostButton.releasePointerCapture?.(event.pointerId);
      }
      setWalkBoost(false);
    }

    boostButton.addEventListener("pointerdown", beginBoostHold, { passive: false });
    boostButton.addEventListener("pointerup", endBoostHold, { passive: false });
    boostButton.addEventListener("pointercancel", endBoostHold, { passive: false });
    boostButton.addEventListener("lostpointercapture", () => {
      setWalkBoost(false);
    });
    boostButton.addEventListener("click", (event) => {
      event.preventDefault();
    });
    boostButton.addEventListener("keydown", (event) => {
      if (event.key !== " " && event.key !== "Enter") return;
      event.preventDefault();
      setWalkBoost(true);
    });
    boostButton.addEventListener("keyup", (event) => {
      if (event.key !== " " && event.key !== "Enter") return;
      event.preventDefault();
      setWalkBoost(false);
    });
    boostButton.addEventListener("blur", () => {
      setWalkBoost(false);
    });

    document.addEventListener("keydown", (event) => {
      if (event.key !== "Escape") return;
      if (state.stepPlayersPanel.open) {
        closeStepPlayersPanel();
        return;
      }
      if (state.leaderboardOpen) {
        closeLeaderboard();
      }
    });

    walkActionButton.addEventListener("click", () => {
      startPendingWalk();
    });

    window.addEventListener("resize", () => {
      if (!state.board) return;
      computeScales();
      state.camera.scale = clamp(state.camera.scale, state.camera.minScale, state.camera.maxScale);
      applyCamera();
      renderBoardCanvas();
      renderSelfLayer();
      updateWalkActionButton();
    }, { passive: true });

    function previewPlayersForStep(stepIndex) {
      const target = Number(stepIndex);
      if (!Number.isFinite(target)) return [];
      return (state.bootstrap?.players || []).filter((player) => Number(player.positionStep ?? -1) === target);
    }

    function previewRewardCandidates(summary = state.bootstrap?.summary) {
      const reachedStep = Number(summary?.positionStep ?? -1);
      const claimed = new Set(Array.isArray(summary?.claimedRewardIds) ? summary.claimedRewardIds.map((value) => String(value || "")) : []);
      const entries = [];
      const pushReward = (reward, fallbackKind = "coin") => {
        const rewardId = String(reward?.id || "");
        const stepIndex = Number.isInteger(reward?.stepIndex) ? Number(reward.stepIndex) : -1;
        if (!rewardId || claimed.has(rewardId) || stepIndex < 0 || reachedStep < stepIndex) return;
        entries.push({
          id: rewardId,
          rewardId,
          stepIndex,
          kind: String(reward?.kind || reward?.meta?.kind || fallbackKind),
          amount: Number(reward?.amount ?? reward?.meta?.amount ?? 1),
          itemCode: String(reward?.itemCode || reward?.meta?.itemCode || ""),
          rewardTemplateId: String(reward?.rewardTemplateId || "")
        });
      };
      (state.board?.rewards || []).forEach((reward) => pushReward(reward));
      (state.board?.rewardNodes || []).forEach((reward) => pushReward(reward));
      return entries;
    }

    function simulatedPreviewClaim() {
      const claimedRewards = previewRewardCandidates();
      const current = state.bootstrap?.summary || {};
      const claimedRewardIds = new Set(Array.isArray(current.claimedRewardIds) ? current.claimedRewardIds.map((value) => String(value || "")) : []);
      claimedRewards.forEach((reward) => {
        if (reward.rewardId) claimedRewardIds.add(String(reward.rewardId));
      });
      const summary = {
        ...current,
        claimedRewardIds: Array.from(claimedRewardIds),
        claimableRewardCount: 0
      };
      return {
        ok: true,
        previewOnly: true,
        claimedRewards,
        summary
      };
    }

    function buildPreviewBootstrap(board, simulation = {}) {
      const nextBoard = cloneJson(board || state.board || {});
      const maxIndex = Math.max(-1, Number(nextBoard?.steps?.length || 0) - 1);
      const targetStep = clamp(Math.trunc(Number(simulation.step ?? simulation.positionStep ?? -1)), -1, maxIndex);
      const walkFrom = clamp(Math.trunc(Number(simulation.walkFrom ?? targetStep)), -1, maxIndex);
      const autoplay = Boolean(simulation.autoplay || simulation.walk);
      const pendingSteps = autoplay ? Math.max(0, targetStep - walkFrom) : 0;
      const claimedRewardIds = Array.isArray(simulation.claimedRewardIds)
        ? simulation.claimedRewardIds.map((value) => String(value || "")).filter(Boolean)
        : [];
      const friendCount = Math.max(0, Math.min(12, Math.round(Number(simulation.friendCount ?? nextBoard?.meta?.fx?.friendCount ?? 3))));
      const players = Array.isArray(simulation.players)
        ? simulation.players
        : Array.from({ length: Boolean(simulation.showFriends ?? true) ? friendCount : 0 }, (_, index) => ({
          userId: `preview-friend-${index + 1}`,
          displayName: `เพื่อน ${index + 1}`,
          avatarUrl: "",
          positionStep: Math.max(-1, targetStep - index - 1),
          lifetimeSteps: Math.max(0, targetStep - index)
        }));
      const summary = {
        boardCode: nextBoard?.boardCode || initialBoardCode,
        lifetimeSteps: Math.max(0, targetStep + 1),
        positionStep: targetStep,
        lastAnimatedStep: pendingSteps > 0 ? walkFrom : targetStep,
        pendingSteps,
        pendingWalkCount: pendingSteps,
        badgeCount: pendingSteps,
        finished: maxIndex >= 0 && targetStep >= maxIndex,
        claimableRewardCount: 0,
        claimedRewardIds,
        requiresLogin: false
      };
      const bootstrap = {
        ok: true,
        previewOnly: true,
        requiresLogin: false,
        serviceUnavailable: false,
        board: nextBoard,
        summary,
        progress: {
          lifetimeSteps: summary.lifetimeSteps,
          positionStep: summary.positionStep,
          lastAnimatedStep: summary.lastAnimatedStep,
          finished: summary.finished
        },
        pending: {
          startStepIndex: pendingSteps > 0 ? walkFrom + 1 : null,
          endStepIndex: pendingSteps > 0 ? targetStep : null,
          previewRewards: []
        },
        players,
        self: {
          userId: "preview-self",
          displayName: "ME",
          avatarUrl: String(simulation.avatarUrl || ""),
          lifetimeSteps: summary.lifetimeSteps,
          positionStep: targetStep
        },
        leaderboard: {
          all: [],
          weekly: []
        }
      };
      bootstrap.summary.claimableRewardCount = previewRewardCandidates(bootstrap.summary).length;
      return bootstrap;
    }

    async function applyBootstrapData(bootstrapData, options = {}) {
      const previousCamera = state.preview.enabled && state.preview.bootstrapped && options.preserveCamera !== false
        ? { x: state.camera.x, y: state.camera.y, scale: state.camera.scale }
        : null;
      if (state.animationFrame && !options.keepAnimationLoop) {
        window.cancelAnimationFrame(state.animationFrame);
        state.animationFrame = 0;
      }
      state.boardReady = false;
      state.walk.active = false;
      state.walk.pendingClaim = false;
      resetWalkPose();
	      state.board = bootstrapData.board;
	      state.bootstrap = state.preview.enabled
	        ? buildPreviewBootstrap(state.board, options.simulation || state.preview.simulation || {})
	        : applyDebugConfigToBootstrap(bootstrapData);
	      state.board = state.bootstrap.board;
	      state.canvas.softSpriteCache.clear();
	      applyBoardUiSettings();
      syncClaimedRewardState(state.bootstrap.summary);
      if (!state.board?.image) {
        throw new Error("BOARD_UNAVAILABLE");
      }

      renderBoardTrack();
      renderStepLayer();
      renderRewardLayer();
      renderSpriteLayer();
      renderPlayerLayer();
      renderSelfLayer();
      computeScales();
      if (previousCamera) {
        state.camera.scale = clamp(previousCamera.scale, state.camera.minScale, state.camera.maxScale);
        state.camera.x = previousCamera.x;
        state.camera.y = previousCamera.y;
        applyCamera();
      } else {
        state.camera.scale = state.camera.minScale;
      }
      if (previousCamera) {
        // keep current preview viewport
      } else if (focusForDebugZone()) {
        // already focused by debug zone
      } else {
        focusOnSelf(state.preview.enabled);
      }

      if (state.bootstrap.serviceUnavailable) {
        hideMessage();
        syncLoginButton(false);
        showToast("ตอนนี้เชื่อมข้อมูลผู้เล่นไม่สำเร็จ กำลังแสดงแมปแบบจำกัดชั่วคราว", 4200);
      } else if (state.bootstrap.requiresLogin) {
        hideMessage();
        syncLoginButton(true);
      } else {
        hideMessage();
        syncLoginButton(false);
      }

      updateSummary();
      postSummaryToParent();
      postMileageUiStateToParent();
      if (Number(state.bootstrap?.summary?.unmappedOverflowSteps || 0) > 0) {
        showToast(`Mileage ยังนับอยู่ แต่ตอนนี้สุดแมพที่มาร์คแล้ว +${Number(state.bootstrap.summary.unmappedOverflowSteps || 0).toLocaleString()} ช่อง`, 3600);
      }

      await waitForBoardImagesReady();
      state.boardReady = true;
      computeScales();
      if (previousCamera) {
        state.camera.scale = clamp(previousCamera.scale, state.camera.minScale, state.camera.maxScale);
        state.camera.x = previousCamera.x;
        state.camera.y = previousCamera.y;
        applyCamera();
      } else {
        state.camera.scale = state.camera.minScale;
      }
      renderBoardCanvas();
      renderSelfLayer();
      updateWalkActionButton();

      if (previousCamera) {
        // keep current preview viewport
      } else if (focusForDebugZone()) {
        // already focused by debug zone
      } else {
        focusOnSelf(state.preview.enabled);
      }

      if (!state.animationFrame) {
        state.animationFrame = window.requestAnimationFrame(animationLoop);
      }
      if ((state.debug.autoplay || options.autoplay) && canAnimatePendingWalk()) {
        window.setTimeout(() => {
          startPendingWalk();
        }, 80);
      }
      state.preview.bootstrapped = true;
    }

    async function applyPreviewMessage(payload) {
      if (!state.preview.enabled || !payload || payload.type !== "dekpoke-mileage-preview-board") return;
      state.preview.simulation = plainObject(payload.simulation) ? payload.simulation : {};
      const board = plainObject(payload.board) ? payload.board : state.board;
      try {
        if (plainObject(payload.board)) {
          await applyBootstrapData(buildPreviewBootstrap(board, state.preview.simulation), {
            simulation: state.preview.simulation,
            autoplay: Boolean(state.preview.simulation.autoplay),
            keepAnimationLoop: true,
            preserveCamera: true
          });
        } else {
          applyPreviewSimulation(state.preview.simulation);
        }
      } catch (error) {
        console.warn("Mileage preview update failed:", error);
      }
    }

    function applyPreviewSimulation(simulation = {}) {
      if (!state.preview.enabled || !state.board) return;
      state.preview.simulation = plainObject(simulation) ? simulation : {};
      state.walk.active = false;
      state.walk.pendingClaim = false;
      resetWalkPose();
      state.bootstrap = buildPreviewBootstrap(state.board, state.preview.simulation);
      syncClaimedRewardState(state.bootstrap.summary);
      updateSummary();
      renderRewardLayer();
      renderPlayerLayer();
      renderSelfLayer();
      renderBoardCanvas();
      updateWalkActionButton();
      if (Boolean(state.preview.simulation.autoplay) && canAnimatePendingWalk()) {
        startPendingWalk();
      }
    }

    function handlePreviewCommand(command) {
      if (!state.preview.enabled || !state.board) return;
      if (command === "zoom-in") {
        setCameraScale(state.camera.scale * 1.18, viewportCenter());
      } else if (command === "zoom-out") {
        setCameraScale(state.camera.scale / 1.18, viewportCenter());
      } else if (command === "focus-self") {
        focusOnSelf(true, true);
      } else if (command === "fit") {
        state.camera.scale = state.camera.minScale;
        focusOnSelf(false, true, state.camera.minScale);
      }
      renderBoardCanvas();
      renderSelfLayer();
    }

    window.addEventListener("message", (event) => {
      if (!state.preview.enabled || event.origin !== window.location.origin) return;
      if (event.data?.type === "dekpoke-mileage-preview-command") {
        handlePreviewCommand(String(event.data.command || ""));
        return;
      }
      void applyPreviewMessage(event.data);
    });

    async function boot() {
      try {
        showMessage("กำลังโหลดแมป", "กำลังดึงข้อมูลแมป ไอคอนรางวัล และตำแหน่งผู้เล่น");
        const bootstrapData = await fetchMileageJson("bootstrap");
        await applyBootstrapData(bootstrapData, { autoplay: state.debug.autoplay });
      } catch (error) {
        console.error(error);
        syncLoginButton(false);
        showMessage("โหลดกิจกรรมไม่สำเร็จ", "ตรวจสอบว่าไฟล์ board JSON และรูปแมปถูกวางครบ แล้วลองเปิดกิจกรรมอีกครั้ง");
      }
    }

    boot();
  </script>
</body>
</html>
