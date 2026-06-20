import { DiscordSDK } from "@discord/embedded-app-sdk";

function isDiscordActivityContext() {
  const params = new URLSearchParams(window.location.search);
  return window.parent !== window || params.has("frame_id") || params.has("instance_id");
}

function withTimeout(promise, label, ms) {
  return Promise.race([
    promise,
    new Promise((_, reject) => {
      window.setTimeout(() => {
        reject(new Error(`${label} timed out after ${ms}ms`));
      }, ms);
    }),
  ]);
}

async function parseJsonResponse(response) {
  const text = await response.text();
  try {
    return text ? JSON.parse(text) : {};
  } catch (error) {
    throw new Error(`Invalid JSON from token endpoint: ${text.slice(0, 180)}`);
  }
}

export { isDiscordActivityContext };

export async function loginWithDiscordActivity(options = {}) {
  const {
    clientId = "",
    tokenEndpoint = "",
    scopes = ["identify"],
    readyTimeoutMs = 15000,
    authorizeTimeoutMs = 25000,
    authenticateTimeoutMs = 25000,
  } = options;

  if (!clientId) {
    throw new Error("Discord Activity clientId is missing.");
  }
  if (!tokenEndpoint) {
    throw new Error("Discord Activity token endpoint is missing.");
  }
  if (!isDiscordActivityContext()) {
    throw new Error("Discord Activity context was not detected.");
  }

  const discordSdk = new DiscordSDK(clientId);
  await withTimeout(discordSdk.ready(), "discordSdk.ready", readyTimeoutMs);

  const authorizeResult = await withTimeout(
    discordSdk.commands.authorize({
      client_id: clientId,
      response_type: "code",
      prompt: "none",
      scope: scopes,
    }),
    "discordSdk.commands.authorize",
    authorizeTimeoutMs
  );

  const code = String(authorizeResult?.code || "");
  if (!code) {
    throw new Error("Discord Activity did not return an authorization code.");
  }

  const tokenResponse = await fetch(tokenEndpoint, {
    method: "POST",
    credentials: "same-origin",
    headers: {
      "Content-Type": "application/json",
      Accept: "application/json",
    },
    body: JSON.stringify({ code }),
  });

  const tokenPayload = await parseJsonResponse(tokenResponse);
  if (!tokenResponse.ok || !tokenPayload?.ok || !tokenPayload?.access_token) {
    throw new Error(
      String(
        tokenPayload?.message ||
        tokenPayload?.error ||
        `Discord Activity token exchange failed with HTTP ${tokenResponse.status}`
      )
    );
  }

  const authResult = await withTimeout(
    discordSdk.commands.authenticate({
      access_token: tokenPayload.access_token,
    }),
    "discordSdk.commands.authenticate",
    authenticateTimeoutMs
  );

  const authUserId = String(authResult?.user?.id || "");
  const sessionUserId = String(tokenPayload?.session?.user?.discordUserId || "");
  if (!authUserId) {
    throw new Error("Discord Activity authentication did not return a user.");
  }
  if (sessionUserId && sessionUserId !== authUserId) {
    throw new Error("Discord Activity account mismatch.");
  }

  return {
    sdk: discordSdk,
    authorize: authorizeResult,
    token: tokenPayload,
    auth: authResult,
  };
}
