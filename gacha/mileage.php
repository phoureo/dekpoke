<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/Bootstrap.php';
Bootstrap::init();

$boardCode = preg_replace('/[^a-z0-9_-]+/i', '-', strtolower(trim((string) ($_GET['boardCode'] ?? GachaMileageService::DEFAULT_BOARD_CODE)))) ?: GachaMileageService::DEFAULT_BOARD_CODE;
$playerToken = trim((string) ($_GET['player_token'] ?? ''));
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
      left: 0;
      top: 0;
      width: var(--board-width, 941px);
      height: var(--board-height, 10368px);
      background: transparent;
      transform-origin: 0 0;
      will-change: transform;
      contain: layout paint style;
    }

    .board-shell.is-hidden {
      display: none;
    }

    .board-track,
    .board-overlay,
    .overlay-layer {
      position: absolute;
      inset: 0;
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
      border: 1px solid rgba(225, 240, 255, 0.16);
      background: rgba(6, 10, 24, 0.32);
      color: var(--muted);
      font-size: 10px;
      line-height: 1;
      white-space: nowrap;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.14);
      backdrop-filter: blur(10px);
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
      border: 1px solid rgba(236, 246, 255, 0.28);
      border-radius: 999px;
      background: rgba(7, 11, 28, 0.48);
      color: #f4fbff;
      box-shadow: 0 14px 32px rgba(0, 0, 0, 0.28);
      backdrop-filter: blur(14px);
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
      height: 42px;
      padding: 0 16px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border: 1px solid rgba(255, 255, 255, 0.18);
      border-radius: 999px;
      background:
        radial-gradient(circle at 30% 24%, rgba(255, 255, 255, 0.22), transparent 34%),
        linear-gradient(135deg, rgba(255, 222, 143, 0.96), rgba(255, 159, 228, 0.94));
      color: #1d1630;
      font: 700 13px/1 var(--font);
      letter-spacing: 0.01em;
      white-space: nowrap;
      box-shadow: 0 18px 38px rgba(0, 0, 0, 0.28);
      cursor: pointer;
      transition: transform 160ms ease, opacity 160ms ease, box-shadow 160ms ease;
    }

    .boost-button:active {
      transform: translateY(1px) scale(0.98);
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
      border: 1px solid rgba(236, 246, 255, 0.24);
      border-radius: 999px;
      background: rgba(7, 11, 28, 0.46);
      color: #f7fbff;
      box-shadow: 0 14px 32px rgba(0, 0, 0, 0.24);
      backdrop-filter: blur(14px);
      cursor: pointer;
      transition: transform 160ms ease, background 160ms ease;
    }

    .rank-button:active {
      transform: scale(0.96);
      background: rgba(143, 245, 255, 0.2);
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
      right: max(16px, calc(env(safe-area-inset-right) + 16px));
      bottom: max(calc(var(--embed-bottom-offset) + 136px), calc(env(safe-area-inset-bottom) + var(--embed-bottom-offset) + 136px));
      width: min(330px, calc(100vw - 32px));
      max-height: min(520px, calc(100vh - 180px));
      display: grid;
      grid-template-rows: auto auto minmax(0, 1fr);
      gap: 10px;
      padding: 12px;
      border: 1px solid rgba(236, 246, 255, 0.18);
      border-radius: 18px;
      background: rgba(6, 10, 24, 0.78);
      box-shadow: 0 24px 80px rgba(0, 0, 0, 0.42);
      backdrop-filter: blur(18px);
    }

    .leaderboard-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
    }

    .leaderboard-title {
      margin: 0;
      font-size: 14px;
      line-height: 1.1;
    }

    .leaderboard-count {
      color: var(--muted);
      font-size: 11px;
      white-space: nowrap;
    }

    .leaderboard-tabs {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 6px;
    }

    .leaderboard-tab,
    .leaderboard-close,
    .leaderboard-row {
      font: inherit;
    }

    .leaderboard-tab {
      height: 34px;
      border: 1px solid rgba(236, 246, 255, 0.14);
      border-radius: 999px;
      background: rgba(255, 255, 255, 0.06);
      color: var(--muted);
      cursor: pointer;
    }

    .leaderboard-tab.is-active {
      background: rgba(143, 245, 255, 0.18);
      color: var(--ink);
      border-color: rgba(143, 245, 255, 0.32);
    }

    .leaderboard-close {
      width: 30px;
      height: 30px;
      border: 0;
      border-radius: 999px;
      background: rgba(255, 255, 255, 0.08);
      color: var(--ink);
      cursor: pointer;
    }

    .leaderboard-list {
      min-height: 90px;
      overflow: auto;
      display: grid;
      align-content: start;
      gap: 6px;
      padding-right: 2px;
      overscroll-behavior: contain;
    }

    .leaderboard-row {
      width: 100%;
      min-height: 48px;
      display: grid;
      grid-template-columns: 30px 34px minmax(0, 1fr) auto;
      align-items: center;
      gap: 8px;
      padding: 7px;
      border: 1px solid rgba(236, 246, 255, 0.1);
      border-radius: 14px;
      background: rgba(255, 255, 255, 0.055);
      color: var(--ink);
      text-align: left;
      cursor: pointer;
    }

    .leaderboard-rank {
      color: rgba(245, 251, 255, 0.72);
      font-size: 12px;
      font-weight: 700;
      text-align: center;
    }

    .leaderboard-avatar {
      width: 34px;
      height: 34px;
      overflow: hidden;
      border-radius: 999px;
      border: 1px solid rgba(245, 251, 255, 0.48);
      background: rgba(143, 245, 255, 0.18);
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
      font-size: 12px;
      line-height: 1.1;
    }

    .leaderboard-name small {
      color: var(--muted);
      font-size: 10px;
    }

    .leaderboard-score {
      color: #fff4bd;
      font-size: 12px;
      font-weight: 700;
      white-space: nowrap;
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

      .leaderboard-panel {
        right: max(10px, calc(env(safe-area-inset-right) + 10px));
        bottom: max(calc(var(--embed-bottom-offset) + 128px), calc(env(safe-area-inset-bottom) + var(--embed-bottom-offset) + 128px));
        width: min(320px, calc(100vw - 20px));
        max-height: min(480px, calc(100vh - 150px));
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
<body>
  <div id="viewer" class="viewer">
    <div id="scene" class="scene" aria-label="Mileage board viewer">
      <div id="boardShell" class="board-shell is-hidden" aria-label="Mileage board">
        <div id="boardTrack" class="board-track"></div>
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
      <div class="leaderboard-head">
        <div>
          <h2 id="leaderboardTitle" class="leaderboard-title">Leaderboard</h2>
          <div id="leaderboardCount" class="leaderboard-count">0 คน</div>
        </div>
        <button id="leaderboardClose" class="leaderboard-close" type="button" aria-label="ปิด">×</button>
      </div>
      <div class="leaderboard-tabs" role="tablist" aria-label="เลือกอันดับ mileage">
        <button class="leaderboard-tab is-active" type="button" data-rank-tab="all">ทั้งหมด</button>
        <button class="leaderboard-tab" type="button" data-rank-tab="weekly">สัปดาห์</button>
      </div>
      <div id="leaderboardList" class="leaderboard-list"></div>
    </section>

    <button id="boostButton" class="boost-button is-hidden" type="button" aria-label="เร่งความเร็วแอนิเมชัน">
      เร่งให้จบ
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
    const initialBoardCode = <?php echo json_encode($boardCode, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    const initialPlayerToken = <?php echo json_encode($playerToken, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;

    const viewer = document.getElementById("viewer");
    const scene = document.getElementById("scene");
    const boardShell = document.getElementById("boardShell");
    const boardTrack = document.getElementById("boardTrack");
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
      otherSize: 34,
      selfSize: 46,
      otherLift: 28,
      selfLift: 35,
      clusterOffset: 24,
      clusterLift: 19,
      stepBadgeSize: 24,
      stepBadgeOffset: 18
    };
    const rewardPickupIcons = {
      coin: "images/icon_coin.png",
      gem: "images/icon_gem.png",
      ticket: "images/icon_ticket.png",
      potion: "images/icon_gelato.png"
    };

    const state = {
      bootstrap: null,
      board: null,
      boardReady: false,
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
        pathLock: false
      },
      leaderboardTab: "all",
      leaderboardOpen: false,
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
        boost: false,
        pendingClaim: false,
        restoreScale: 1
      },
      claimedRewardIds: new Set(),
      recentClaimedRewardIds: new Set(),
      recentClaimRewardTimer: 0,
      toastTimer: 0,
      animationFrame: 0
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

    function lerp(start, end, t) {
      return start + (end - start) * t;
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
        return [{ src: source, y: 0, h: state.board?.image?.height || 0 }];
      }
      return [];
    }

    function boardStep(index) {
      if (!state.board) return null;
      return state.board.steps?.[index] || null;
    }

    function boardPointFromStep(index) {
      const step = boardStep(index);
      if (!step || !state.board) return null;
      const size = boardSize();
      return {
        x: step.x * size.width,
        y: step.y * size.height
      };
    }

    function boardEntryPoint() {
      if (!state.board) return null;
      const entry = state.board.entry || state.board.steps?.[0] || null;
      const normalizedX = typeof entry?.x === "number" ? entry.x : 0.4123;
      const normalizedY = typeof entry?.y === "number" ? entry.y : 0.9721;
      const size = boardSize();
      return {
        x: normalizedX * size.width,
        y: normalizedY * size.height
      };
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
      const size = boardSize();
      if (typeof reward.x === "number" && typeof reward.y === "number") {
        return {
          x: reward.x * size.width,
          y: reward.y * size.height
        };
      }
      if (Number.isInteger(reward.stepIndex)) {
        return boardPointFromStep(reward.stepIndex);
      }
      return null;
    }

    function spritePoint(sprite) {
      if (!state.board || !sprite || typeof sprite.x !== "number" || typeof sprite.y !== "number") {
        return null;
      }
      const size = boardSize();
      return {
        x: sprite.x * size.width,
        y: sprite.y * size.height
      };
    }

    function pathPointForValue(stepValue) {
      if (!state.board) return null;
      if (!state.board.steps?.length) return boardEntryPoint();
      const maxIndex = state.board.steps.length - 1;
      const clampedValue = clamp(stepValue, -1, maxIndex);
      if (clampedValue < 0) {
        const entryPoint = boardEntryPoint();
        const firstPoint = boardPointFromStep(0) || entryPoint;
        if (!entryPoint || !firstPoint) return null;
        const t = clampedValue + 1;
        return {
          x: lerp(entryPoint.x, firstPoint.x, t),
          y: lerp(entryPoint.y, firstPoint.y, t)
        };
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
      boardShell.style.transform = `translate3d(${state.camera.x}px, ${state.camera.y}px, 0) scale(${state.camera.scale})`;
      updateZoomSlider();
    }

    function isPathScrollAssistAvailable() {
      return Boolean(
        state.board
        && Array.isArray(state.board.steps)
        && state.board.steps.length > 0
        && !state.walk.active
        && state.camera.scale > (state.camera.minScale + 0.04)
      );
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
      const absX = Math.abs(Number(horizontalDelta) || 0);
      const absY = Math.abs(Number(verticalDelta) || 0);
      const touchBoost = Boolean(options.touchBoost);
      if (absY < (touchBoost ? 6 : 10)) return 0;
      const share = absY / Math.max(1, absX + absY);
      const minimumShare = touchBoost ? 0.58 : 0.66;
      if (share < minimumShare) return 0;
      const speed = Math.hypot(absX, absY);
      const dominance = clamp((share - minimumShare) / (1 - minimumShare), 0, 1);
      const damping = touchBoost
        ? 1 - clamp((speed - 58) / 360, 0, 0.46)
        : 1 - clamp((speed - 42) / 280, 0, 0.72);
      return dominance * (touchBoost ? 0.68 : 0.28) * damping;
    }

    function shouldTouchPathLock(horizontalDelta, verticalDelta) {
      const absX = Math.abs(Number(horizontalDelta) || 0);
      const absY = Math.abs(Number(verticalDelta) || 0);
      if (absY < 18) return false;
      const share = absY / Math.max(1, absX + absY);
      return share >= 0.74;
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
        startAt: performance.now(),
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
      if (immediate) {
        applyCamera();
        return;
      }
      clampCamera();
      boardShell.style.transform = `translate3d(${state.camera.x}px, ${state.camera.y}px, 0) scale(${state.camera.scale})`;
      updateZoomSlider();
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

    function renderBoardTrack() {
      const segments = boardSegments();
      const size = boardSize();
      boardShell.style.setProperty("--board-width", `${size.width}px`);
      boardShell.style.setProperty("--board-height", `${size.height}px`);
      boardTrack.innerHTML = segments
        .map((segment) => {
          const y = Number(segment.y || 0);
          const h = Number(segment.h || 0);
          const style = h > 0
            ? `top:${y}px; height:${h}px; object-fit:cover;`
            : `top:${y}px;`;
          return `<img src="${escapeHtml(segment.src || "")}" alt="" loading="eager" decoding="async" style="${style}" />`;
        })
        .join("");
      boardShell.classList.toggle("is-hidden", segments.length === 0);
    }

    function waitForBoardImagesReady() {
      const images = Array.from(boardTrack.querySelectorAll("img"));
      if (images.length === 0) {
        return Promise.resolve();
      }
      return Promise.all(images.map((image) => new Promise((resolve) => {
        if (image.complete) {
          resolve(null);
          return;
        }
        image.addEventListener("load", () => resolve(null), { once: true });
        image.addEventListener("error", () => resolve(null), { once: true });
      })));
    }

    function renderRewardLayer() {
      const rewards = Array.isArray(state.board?.rewards) ? state.board.rewards : [];
      const reachedStep = Number(state.bootstrap?.summary?.positionStep ?? -1);
      const canClaim = !state.bootstrap?.requiresLogin;
      const claimedRewardIds = state.claimedRewardIds;
      const recentClaimedRewardIds = state.recentClaimedRewardIds;
      rewardLayer.innerHTML = rewards.map((reward) => {
        const point = rewardPoint(reward);
        const pos = pointToPercent(point);
        if (!pos) return "";
        const rewardId = String(reward.id || "").trim();
        const stepIndex = Number.isInteger(reward.stepIndex) ? Number(reward.stepIndex) : -1;
        const claimed = rewardId !== "" && claimedRewardIds.has(rewardId);
        const unlocked = canClaim && stepIndex >= 0 && reachedStep >= stepIndex && !claimed;
        const justClaimed = rewardId !== "" && recentClaimedRewardIds.has(rewardId);
        const stateClass = claimed ? "is-claimed" : (unlocked ? "is-unlocked" : "is-locked");
        const title = claimed
          ? "รับรางวัลจุดนี้แล้ว"
          : (unlocked ? "กดรับรางวัลที่ปลดล็อกแล้ว" : "รางวัลจะปลดล็อกเมื่อเดินถึงช่องนี้");
        return `
          <div
            class="reward-marker is-${escapeHtml(reward.kind || "coin")} ${stateClass}${justClaimed ? " is-just-claimed" : ""}"
            data-reward-id="${escapeHtml(rewardId)}"
            data-reward-step-index="${stepIndex}"
            style="left:${pos.x}%; top:${pos.y}%; --reward-marker-size:${rewardMarkerSize(reward)}px;"
            title="${escapeHtml(title)}"
          >
            ${rewardMarkerInnerMarkup(reward, claimed)}
          </div>
        `;
      }).join("");
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

    function spriteFrameIndex(sprite, ts = performance.now()) {
      const frameCount = Math.max(1, Number(sprite?.frameCount || 1));
      const fps = Math.max(1, Number(sprite?.fps || 12));
      const frame = Math.floor((ts / 1000) * fps);
      const mode = String(sprite?.mode || "loop");
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
      const sprites = Array.isArray(state.board?.sprites) ? state.board.sprites : [];
      spriteLayer.innerHTML = sprites.map((sprite, index) => {
        const point = spritePoint(sprite);
        const pos = pointToPercent(point);
        const src = String(sprite.src || "").trim();
        if (!pos || src === "") return "";
        const safeSrc = escapeCssUrl(src);
        const columns = Math.max(1, Number(sprite.columns || 1));
        const rows = Math.max(1, Number(sprite.rows || 1));
        const width = Math.max(1, Number(sprite.width || 48));
        const height = Math.max(1, Number(sprite.height || 48));
        return `
          <div
            class="sprite-marker"
            data-sprite-index="${index}"
            style="left:${pos.x}%; top:${pos.y}%; width:${width}px; height:${height}px; background-image:url('${safeSrc}'); background-size:${columns * 100}% ${rows * 100}%;"
            title="${escapeHtml(sprite.label || sprite.id || "sprite")}"
          ></div>
        `;
      }).join("");
      updateSpriteLayer();
    }

    function updateSpriteLayer(ts = performance.now()) {
      if (!spriteLayer || !state.board?.sprites?.length) return;
      for (const node of spriteLayer.querySelectorAll("[data-sprite-index]")) {
        const index = Number(node.dataset.spriteIndex || -1);
        const sprite = state.board.sprites[index];
        if (!sprite) continue;
        const frame = spriteFrameIndex(sprite, ts);
        node.style.backgroundPosition = spriteBackgroundPosition(sprite, frame);
      }
    }

    function renderStepLayer() {
      const steps = Array.isArray(state.board?.steps) ? state.board.steps : [];
      const groups = new Map();
      steps.forEach((step, index) => {
        const point = boardPointFromStep(index);
        if (!point) return;
        const key = `${Math.round(point.x * 10) / 10}:${Math.round(point.y * 10) / 10}`;
        if (!groups.has(key)) {
          groups.set(key, { point, indexes: [] });
        }
        groups.get(key).indexes.push(index);
      });
      stepLayer.innerHTML = Array.from(groups.values()).map((group) => {
        const firstIndex = group.indexes[0] || 0;
        const { offsetX, offsetY } = stepBadgeOffset(group.point, firstIndex);
        const pos = pointToPercent(group.point, offsetX, offsetY);
        if (!pos) return "";
        if (group.indexes.length > 1) {
          return `
            <div
              class="step-badge is-stack"
              style="left:${pos.x}%; top:${pos.y}%;"
              title="ช่อง ${group.indexes.map((index) => index + 1).join(", ")}"
            >
              ${group.indexes.map((index) => `<span>${index + 1}</span>`).join("")}
            </div>
          `;
        }
        return `
          <div
            class="step-badge"
            style="left:${pos.x}%; top:${pos.y}%; --step-badge-size:${markerStyle.stepBadgeSize}px;"
            title="ช่อง ${firstIndex + 1}"
          >
            ${firstIndex + 1}
          </div>
        `;
      }).join("");
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
      const nodes = [];
      const groups = clusterPlayers();
      for (const [stepKey, players] of groups.entries()) {
        const point = stepKey === "__entry__"
          ? boardEntryPoint()
          : boardPointFromStep(Number(stepKey));
        if (!point) continue;

        players.slice(0, 3).forEach((player, index) => {
          const angle = (Math.PI * 2 * index) / Math.max(1, Math.min(players.length, 3));
          const offset = players.length > 1 ? markerStyle.clusterOffset : 0;
          nodes.push(avatarMarkerMarkup(player, point, {
            size: markerStyle.otherSize,
            liftPx: markerStyle.otherLift,
            offsetX: Math.cos(angle) * offset,
            offsetY: Math.sin(angle) * offset
          }));
        });

        if (players.length > 3) {
          const pos = pointToPercent(
            point,
            markerStyle.clusterOffset + 8,
            -markerStyle.clusterLift
          );
          if (pos) {
            nodes.push(`
              <div class="cluster-badge" style="left:${pos.x}%; top:${pos.y}%;">
                +${players.length - 3}
              </div>
            `);
          }
        }
      }
      playerLayer.innerHTML = nodes.join("");
    }

    function leaderboardRows() {
      const leaderboard = state.bootstrap?.leaderboard || {};
      const tab = state.leaderboardTab === "weekly" ? "weekly" : "all";
      return Array.isArray(leaderboard[tab]) ? leaderboard[tab] : [];
    }

    function findPlayerByUserId(userId) {
      const target = String(userId || "");
      if (!target) return null;
      const self = state.bootstrap?.self || null;
      if (self && String(self.userId || "") === target) return self;
      return (state.bootstrap?.players || []).find((player) => String(player.userId || "") === target)
        || leaderboardRows().find((player) => String(player.userId || "") === target)
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
        leaderboardList.innerHTML = `<div class="pill">ยังไม่มีคนเดินอย่างน้อย 1 ช่อง</div>`;
        return;
      }
      leaderboardList.innerHTML = rows.map((player, index) => {
        const avatarUrl = String(player.avatarUrl || "");
        const displayName = String(player.displayName || "Player");
        const score = Number(tab === "weekly" ? player.weeklySteps : player.lifetimeSteps) || Number(player.score || 0);
        const stepText = Number(player.positionStep || -1) >= 0 ? `ช่อง ${Number(player.positionStep) + 1}` : "ยังอยู่จุดเริ่ม";
        return `
          <button class="leaderboard-row" type="button" data-rank-user-id="${escapeHtml(player.userId || "")}">
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

    async function refreshLeaderboard() {
      try {
        const data = await fetchMileageJson("leaderboard");
        if (data?.leaderboard) {
          state.bootstrap.leaderboard = data.leaderboard;
          renderLeaderboard();
        }
      } catch (error) {
        console.warn("Mileage leaderboard failed:", error);
      }
    }

    function openLeaderboard() {
      state.leaderboardOpen = true;
      leaderboardPanel.classList.remove("is-hidden");
      leaderboardPanel.setAttribute("aria-hidden", "false");
      renderLeaderboard();
      void refreshLeaderboard();
    }

    function closeLeaderboard() {
      state.leaderboardOpen = false;
      leaderboardPanel.classList.add("is-hidden");
      leaderboardPanel.setAttribute("aria-hidden", "true");
    }

    function renderSelfLayer(ts = performance.now()) {
      const selfPlayer = state.bootstrap?.self || null;
      const point = currentSelfPoint();
      if (!selfPlayer || !point) {
        selfLayer.innerHTML = "";
        return;
      }
      const liftPx = state.walk.active
        ? state.walk.visualLiftPx
        : markerStyle.selfLift + Math.sin(ts / 360) * 2;
      const scaleX = state.walk.active ? state.walk.visualScaleX : 1;
      const scaleY = state.walk.active ? state.walk.visualScaleY : 1;
      const rotateDeg = state.walk.active ? state.walk.visualTiltDeg : Math.sin(ts / 520) * 0.8;
      selfLayer.innerHTML = `${walkGroundEffectMarkup(point)}${avatarMarkerMarkup(selfPlayer, point, {
        size: markerStyle.selfSize,
        liftPx,
        self: true,
        scaleX,
        scaleY,
        rotateDeg
      })}`;
    }

    function resetWalkPose() {
      state.walk.visualLiftPx = markerStyle.selfLift;
      state.walk.visualScaleX = 1;
      state.walk.visualScaleY = 1;
      state.walk.visualTiltDeg = 0;
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

    function walkSegmentDurationMs(fromValue) {
      const remainingSteps = walkRemainingSteps(fromValue);
      const baseDuration = state.walk.boost
        ? 210 - ((remainingSteps - 1) * 8)
        : 560 - ((remainingSteps - 1) * 10);
      const clampedDuration = state.walk.boost
        ? clamp(baseDuration, 110, 210)
        : clamp(baseDuration, 360, 560);
      return fromValue < 0 && !state.walk.boost ? clampedDuration + 40 : clampedDuration;
    }

    function walkSegmentHopHeight(fromValue, toValue) {
      const fromPoint = pathPointForValue(fromValue);
      const toPoint = pathPointForValue(toValue);
      if (!fromPoint || !toPoint) {
        return state.walk.boost ? 22 : 28;
      }
      const distance = Math.hypot(toPoint.x - fromPoint.x, toPoint.y - fromPoint.y);
      const height = clamp(distance * 0.22, 24, 42);
      return state.walk.boost ? height * 0.78 : height;
    }

    function walkSegmentTiltDirection(fromValue, toValue) {
      const fromPoint = pathPointForValue(fromValue);
      const toPoint = pathPointForValue(toValue);
      const deltaX = Number(toPoint?.x || 0) - Number(fromPoint?.x || 0);
      return deltaX === 0 ? 1 : Math.sign(deltaX);
    }

    function sampleWalkSegment(progress, fromValue, toValue) {
      const p = clamp(progress, 0, 1);
      const windupEnd = 0.16;
      const movePhase = p <= windupEnd
        ? 0
        : easeInOutCubic((p - windupEnd) / (1 - windupEnd));
      const hopHeight = walkSegmentHopHeight(fromValue, toValue);
      const hop = Math.sin(movePhase * Math.PI);
      const windup = p < windupEnd ? easeOutCubic(p / windupEnd) : 0;
      const landingT = p > 0.74 ? (p - 0.74) / 0.26 : 0;
      const landingSquash = landingT > 0 ? Math.sin(clamp(landingT, 0, 1) * Math.PI) : 0;
      const reboundT = p > 0.84 ? (p - 0.84) / 0.16 : 0;
      const rebound = reboundT > 0 ? Math.sin(clamp(reboundT, 0, 1) * Math.PI) * 0.22 : 0;
      const tiltDirection = walkSegmentTiltDirection(fromValue, toValue);

      return {
        currentValue: lerp(fromValue, toValue, movePhase),
        liftPx: markerStyle.selfLift + (hopHeight * hop) + (hopHeight * rebound * 0.18),
        scaleX: 1 + (windup * 0.14) - (hop * 0.08) + (landingSquash * 0.17),
        scaleY: 1 - (windup * 0.12) + (hop * 0.13) - (landingSquash * 0.16) + (rebound * 0.05),
        tiltDeg: tiltDirection * ((hop * 6.8) - (windup * 2.4) - (landingSquash * 2.4) + (rebound * 1.8))
      };
    }

    function setWalkSegment(fromValue, ts = performance.now()) {
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
      boostButton.disabled = !visible || state.walk.boost;
      boostButton.textContent = state.walk.boost ? "กำลังเร่ง..." : "เร่งให้จบ";
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

    function enableWalkBoost() {
      if (!state.walk.active || state.walk.boost) return;
      const now = performance.now();
      const elapsed = now - state.walk.segmentStartAt;
      const progress = state.walk.segmentDurationMs > 0
        ? clamp(elapsed / state.walk.segmentDurationMs, 0, 1)
        : 0;
      state.walk.boost = true;
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
      focusOnSelf(false, true, state.walk.restoreScale || state.camera.minScale);
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
      state.walk.startAt = performance.now();
      state.walk.boost = false;
      state.walk.restoreScale = clamp(state.camera.scale, state.camera.minScale, state.camera.maxScale);
      resetWalkPose();
      setWalkSegment(fromValue, state.walk.startAt);

      focusOnWalkArea(true, true, Math.max(state.walk.restoreScale, state.camera.walkScale));
      updateBoostButton();
      updateWalkActionButton();
      renderSelfLayer();
    }

    function updateWalk(ts) {
      if (!state.walk.active) return;

      const duration = Math.max(1, state.walk.segmentDurationMs);
      const elapsed = ts - state.walk.segmentStartAt;
      const t = clamp(elapsed / duration, 0, 1);
      const motion = sampleWalkSegment(t, state.walk.segmentFromValue, state.walk.segmentToValue);
      state.walk.currentValue = motion.currentValue;
      state.walk.visualLiftPx = motion.liftPx;
      state.walk.visualScaleX = motion.scaleX;
      state.walk.visualScaleY = motion.scaleY;
      state.walk.visualTiltDeg = motion.tiltDeg;

      const point = pathPointForValue(state.walk.currentValue);
      if (point) {
        nudgeCameraToPoint(point, {
          alignY: 0.67,
          followStrength: state.walk.boost ? 0.31 : 0.22
        });
      }

      if (t >= 1) {
        state.walk.currentValue = state.walk.segmentToValue;
        if (state.walk.segmentToValue >= state.walk.toValue) {
          finishWalkAndClaim();
          return;
        }
        setWalkSegment(state.walk.segmentToValue, ts);
      }
    }

    function animationLoop(ts) {
      updateCameraMomentum(ts);
      updateCameraTween(ts);
      updateWalk(ts);
      updateSpriteLayer(ts);
      renderSelfLayer(ts);
      updateWalkActionButton();
      state.animationFrame = window.requestAnimationFrame(animationLoop);
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
          state.cameraMotion.lastAt = performance.now();
        }
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
      state.cameraMotion.lastAt = performance.now();
      applyCamera();
    }, { passive: false });

    document.addEventListener("gesturestart", preventViewportGesture, { passive: false });
    document.addEventListener("gesturechange", preventViewportGesture, { passive: false });
    document.addEventListener("gestureend", preventViewportGesture, { passive: false });
    document.addEventListener("touchmove", preventViewportGesture, { passive: false });

    zoomSlider.addEventListener("input", () => {
      setCameraScale(scaleFromZoomSlider(), viewportCenter());
    });

    rewardLayer.addEventListener("pointerdown", (event) => {
      if (event.target.closest(".reward-marker")) {
        event.stopPropagation();
      }
    });

    rewardLayer.addEventListener("click", (event) => {
      const marker = event.target.closest(".reward-marker");
      if (!marker) return;
      event.preventDefault();
      if (state.bootstrap?.requiresLogin) {
        showToast("Sign in Discord ก่อนเพื่อรับรางวัล");
        syncLoginButton(true);
        return;
      }
      const stepIndex = Number(marker.dataset.rewardStepIndex ?? -1);
      const reachedStep = Number(state.bootstrap?.summary?.positionStep ?? -1);
      if (!Number.isFinite(stepIndex) || stepIndex < 0 || reachedStep < stepIndex) {
        showToast("เดินถึงช่องนี้ก่อน แล้วค่อยกลับมากดรับ");
        return;
      }
      if (!state.walk.pendingClaim) {
        const markerRect = marker.getBoundingClientRect();
        state.walk.pendingClaim = true;
        void claimPendingRewards({
          quietEmpty: false,
          sourcePoint: {
            x: markerRect.left + markerRect.width / 2,
            y: markerRect.top + markerRect.height / 2
          }
        });
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
        focusOnPlayer(player, true);
      }
    });

    boostButton.addEventListener("click", () => {
      enableWalkBoost();
    });

    walkActionButton.addEventListener("click", () => {
      startPendingWalk();
    });

    window.addEventListener("resize", () => {
      if (!state.board) return;
      computeScales();
      state.camera.scale = clamp(state.camera.scale, state.camera.minScale, state.camera.maxScale);
      applyCamera();
      renderSelfLayer();
      updateWalkActionButton();
    }, { passive: true });

    async function boot() {
      try {
        showMessage("กำลังโหลดแมป", "กำลังดึงข้อมูลแมป ไอคอนรางวัล และตำแหน่งผู้เล่น");
        state.bootstrap = await fetchMileageJson("bootstrap");
        state.board = state.bootstrap.board;
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
        state.camera.scale = state.camera.minScale;
        if (summaryPendingWalkSteps() > 0) {
          focusOnWalkArea(false);
        } else {
          focusOnSelf(false);
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
        if (Number(state.bootstrap?.summary?.unmappedOverflowSteps || 0) > 0) {
          showToast(`Mileage ยังนับอยู่ แต่ตอนนี้สุดแมพที่มาร์คแล้ว +${Number(state.bootstrap.summary.unmappedOverflowSteps || 0).toLocaleString()} ช่อง`, 3600);
        }

        await waitForBoardImagesReady();
        state.boardReady = true;
        computeScales();
        state.camera.scale = state.camera.minScale;
        renderSelfLayer();
        updateWalkActionButton();

        if (summaryPendingWalkSteps() > 0) {
          focusOnWalkArea(false);
        } else {
          focusOnSelf(false);
        }

        if (state.animationFrame) {
          window.cancelAnimationFrame(state.animationFrame);
        }
        state.animationFrame = window.requestAnimationFrame(animationLoop);
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
