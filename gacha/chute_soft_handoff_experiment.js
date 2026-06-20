/*
  Backup only. This file is not loaded by the gacha page.
  It preserves the softer chute handoff experiment in case we want to try it again.
*/

function chuteSoftHandoffGatherPoint(radius = vortex.ballRadius) {
  return {
    x: globe.cx + globe.rx * 0.5,
    y: Math.min(chamberFloor.y - radius * 1.06, globe.cy + globe.ry * 0.76)
  };
}

function chuteSoftHandoffPocketPoint(radius = vortex.ballRadius) {
  return {
    x: globe.cx + globe.rx * 0.58,
    y: Math.min(chamberFloor.y - radius * 0.98, globe.cy + globe.ry * 0.8)
  };
}

function applyChuteSoftHandoffGuide(selected, progress, stepDt) {
  const handoffEase = state.releasing ? smoothstep((progress - 0.76) / 0.16) : 0;
  const target = chuteSoftHandoffGatherPoint(selected.r);
  const guideEase = state.releasing
    ? smoothstep((progress - 0.28) / 0.56)
    : smoothstep((progress - 0.46) / 0.54);
  const guideStrength = state.releasing
    ? 1.8 + guideEase * 3.8 + state.releasePower * 0.55
    : 1.6 + guideEase * (2.4 + state.holdPower * 1.6);
  selected.vx += (target.x - selected.x) * guideStrength * stepDt;
  selected.vy += (target.y - selected.y) * guideStrength * stepDt;
  selected.vx += (58 * guideEase + 34 * handoffEase) * stepDt;
  selected.vy += 18 * handoffEase * stepDt;
  selected.depth = Math.max(selected.depth || 0, 0.22 + handoffEase * 0.2);
}

function drawChuteSoftHandoffGlow(ts) {
  const power = state.chuteGlowPower;
  if (power <= 0.002) return;

  const pocket = chuteSoftHandoffPocketPoint(vortex.ballRadius);
  const cx = pocket.x + 2;
  const cy = pocket.y + 6;
  const exitX = outputBallStart.x + outputBallStart.size * 0.52;
  const exitY = outputBallStart.y + outputBallStart.size * 0.5;
  const pulse = 0.72 + Math.sin(ts / 180) * 0.28;
  ctx.save();
  ctx.globalCompositeOperation = "lighter";

  const glow = ctx.createRadialGradient(cx, cy, 4, cx, cy, 128);
  glow.addColorStop(0, `rgba(255, 245, 186, ${0.38 * power})`);
  glow.addColorStop(0.3, `rgba(255, 137, 83, ${0.24 * power})`);
  glow.addColorStop(0.66, `rgba(97, 239, 255, ${0.1 * power})`);
  glow.addColorStop(1, "rgba(255, 137, 83, 0)");
  ctx.fillStyle = glow;
  ctx.beginPath();
  ctx.arc(cx, cy, 128, 0, Math.PI * 2);
  ctx.fill();

  const stream = ctx.createLinearGradient(cx, cy, exitX, exitY);
  stream.addColorStop(0, `rgba(255, 250, 206, ${0.26 * power})`);
  stream.addColorStop(0.42, `rgba(112, 242, 255, ${0.1 * power})`);
  stream.addColorStop(1, "rgba(112, 242, 255, 0)");
  ctx.strokeStyle = stream;
  ctx.lineWidth = 4 + power * 3;
  ctx.lineCap = "round";
  ctx.beginPath();
  ctx.moveTo(cx - 4, cy + 4);
  ctx.quadraticCurveTo(cx - 22, cy + 48, exitX + 4, exitY - 10);
  ctx.stroke();

  ctx.strokeStyle = `rgba(255, 235, 168, ${0.32 * power * pulse})`;
  ctx.lineWidth = 3 + power * 3;
  ctx.beginPath();
  ctx.arc(cx, cy, 30 + pulse * 8, -0.38, Math.PI * 1.42);
  ctx.stroke();

  for (let i = 0; i < 7; i += 1) {
    const t = (ts / 520 + i * 0.137) % 1;
    const ease = smoothstep(t);
    const x = cx + (exitX - cx) * ease + Math.sin(ts / 120 + i) * 4;
    const y = cy + (exitY - cy) * ease + Math.cos(ts / 150 + i * 1.7) * 3;
    const r = (2.2 + i % 3) * (1 - t * 0.55);
    ctx.fillStyle = i % 2 ? `rgba(255, 247, 184, ${0.38 * power})` : `rgba(112, 242, 255, ${0.24 * power})`;
    ctx.beginPath();
    ctx.arc(x, y, r, 0, Math.PI * 2);
    ctx.fill();
  }
  ctx.restore();
}
