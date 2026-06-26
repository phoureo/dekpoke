(function () {
  "use strict";

  const DEFAULT_API_URL = "asset-manifest-api.php";
  const state = {
    apiUrl: DEFAULT_API_URL,
    manifestPromise: null
  };

  function configure(options = {}) {
    if (typeof options.apiUrl === "string" && options.apiUrl.trim()) {
      state.apiUrl = options.apiUrl.trim();
    }
    if (options.reset === true) {
      state.manifestPromise = null;
    }
  }

  function cloneArray(value) {
    return Array.isArray(value) ? value.slice() : [];
  }

  function stripQueryAndHash(value) {
    return String(value || "").replace(/[?#].*$/, "");
  }

  function canonicalAssetPath(value) {
    let src = stripQueryAndHash(String(value || "").trim());
    if (!src) return "";
    src = src.replace(/\\/g, "/");

    try {
      if (/^https?:/i.test(src)) {
        const url = new URL(src, window.location.href);
        src = url.pathname || "";
      }
    } catch (_error) {
      return "";
    }

    src = src.replace(/^https?:\/\/[^/]+\//i, "");
    src = src.replace(/^\/?workspace\/gacha\//i, "");
    src = src.replace(/^\/?gacha\//i, "");
    src = src.replace(/^\.\//, "");
    src = src.replace(/^\/+/, "");
    return src;
  }

  function sameOriginRelativeUrl(value) {
    try {
      const url = new URL(value, window.location.href);
      if (url.origin === window.location.origin) {
        return `${url.pathname}${url.search}${url.hash}`;
      }
      return url.toString();
    } catch (_error) {
      return value;
    }
  }

  function withVersion(urlString, versionValue) {
    const version = String(versionValue || "").trim();
    if (!version) return urlString;
    try {
      const url = new URL(urlString, window.location.href);
      url.searchParams.set("assetv", version);
      return sameOriginRelativeUrl(url.toString());
    } catch (_error) {
      const joiner = String(urlString).includes("?") ? "&" : "?";
      return `${urlString}${joiner}assetv=${encodeURIComponent(version)}`;
    }
  }

  async function loadManifest() {
    if (state.manifestPromise) {
      return state.manifestPromise;
    }

    const url = new URL(state.apiUrl || DEFAULT_API_URL, window.location.href);
    url.searchParams.set("action", "live_manifest");
    url.searchParams.set("_", String(Date.now()));

    state.manifestPromise = fetch(url, {
      credentials: "same-origin",
      cache: "no-store"
    })
      .then(async (response) => {
        const data = await response.json().catch(() => null);
        if (!response.ok || !data || data.ok === false || !data.manifest) {
          throw new Error(data?.message || data?.code || `HTTP ${response.status}`);
        }
        return {
          manifest: data.manifest,
          pageDefinitions: cloneArray(data.pageDefinitions)
        };
      })
      .catch((error) => {
        state.manifestPromise = null;
        throw error;
      });

    return state.manifestPromise;
  }

  function manifestAssets(payload) {
    return Array.isArray(payload?.manifest?.assets) ? payload.manifest.assets : [];
  }

  function manifestGroups(payload) {
    return Array.isArray(payload?.manifest?.groups) ? payload.manifest.groups : [];
  }

  function groupMap(payload) {
    const map = new Map();
    manifestGroups(payload).forEach((group) => {
      const id = String(group?.id || "").trim();
      if (id) map.set(id, group);
    });
    return map;
  }

  function assetMatchesPage(asset, pageId) {
    const pages = Array.isArray(asset?.pages) ? asset.pages : [];
    if (!pages.length) return true;
    return pages.includes(pageId);
  }

  function findActiveAsset(payload, pageId, src) {
    const path = canonicalAssetPath(src);
    if (!path) return null;
    return manifestAssets(payload).find((asset) => (
      String(asset?.path || "") === path
      && asset?.enabled !== false
      && assetMatchesPage(asset, pageId)
    )) || null;
  }

  function resolvedVersion(payload, asset) {
    const mode = String(asset?.versionMode || "none").trim().toLowerCase();
    if (mode === "custom") {
      return String(asset?.customVersion || "").trim();
    }
    if (mode === "inherit-content-version") {
      return String(payload?.manifest?.meta?.contentVersion || "").trim();
    }
    return "";
  }

  function fallbackResolution(src, fallbackOptions = {}) {
    return {
      originalSrc: src,
      canonicalPath: canonicalAssetPath(src),
      url: src,
      cachePolicy: fallbackOptions.noStore ? "no-store" : "default",
      mimeType: String(fallbackOptions.mimeType || "").trim(),
      enabled: true,
      preload: true,
      manifestFound: false,
      group: null,
      asset: null
    };
  }

  function resolveWithManifest(payload, pageId, src, fallbackOptions = {}) {
    const fallback = fallbackResolution(src, fallbackOptions);
    const asset = findActiveAsset(payload, pageId, src);
    if (!asset) {
      return fallback;
    }

    const groups = groupMap(payload);
    const group = groups.get(String(asset.groupId || "").trim()) || null;
    const version = resolvedVersion(payload, asset);
    const url = withVersion(src, version);
    const cachePolicy = String(asset.cachePolicy || "").trim().toLowerCase() === "no-store"
      ? "no-store"
      : "default";
    const mimeType = String(asset.mimeType || "").trim() || fallback.mimeType;

    return {
      originalSrc: src,
      canonicalPath: canonicalAssetPath(src),
      url,
      cachePolicy,
      mimeType,
      enabled: asset.enabled !== false,
      preload: asset.preload !== false,
      manifestFound: true,
      group,
      asset
    };
  }

  async function resolveAsset(pageId, src, fallbackOptions = {}) {
    if (!src) {
      return fallbackResolution(src, fallbackOptions);
    }

    try {
      const payload = await loadManifest();
      return resolveWithManifest(payload, pageId, src, fallbackOptions);
    } catch (_error) {
      return fallbackResolution(src, fallbackOptions);
    }
  }

  async function buildPreloadEntries(pageId, defaults = []) {
    const normalizedDefaults = defaults.map((item) => ({
      src: item?.src || "",
      options: item?.options || {},
      label: item?.label || ""
    })).filter((item) => item.src);

    let payload = null;
    try {
      payload = await loadManifest();
    } catch (_error) {
      return normalizedDefaults;
    }

    const groups = groupMap(payload);
    const seen = new Set();
    const entries = [];

    normalizedDefaults.forEach((item) => {
      const resolved = resolveWithManifest(payload, pageId, item.src, item.options || {});
      const key = resolved.canonicalPath || canonicalAssetPath(item.src);
      if (key) seen.add(key);
      entries.push({
        src: resolved.url,
        originalSrc: item.src,
        options: {
          ...item.options,
          mimeType: resolved.mimeType,
          noStore: resolved.cachePolicy === "no-store"
        },
        label: resolved.group?.preloadLabel || item.label || "Loading assets"
      });
    });

    manifestAssets(payload)
      .filter((asset) => asset?.enabled !== false && asset?.preload !== false && assetMatchesPage(asset, pageId))
      .forEach((asset) => {
        const key = canonicalAssetPath(asset.path || "");
        if (!key || seen.has(key)) return;
        seen.add(key);
        const group = groups.get(String(asset.groupId || "").trim()) || null;
        const resolved = resolveWithManifest(payload, pageId, asset.path || "", {});
        entries.push({
          src: resolved.url,
          originalSrc: asset.path,
          options: {
            mimeType: resolved.mimeType,
            noStore: resolved.cachePolicy === "no-store"
          },
          label: group?.preloadLabel || "Loading assets"
        });
      });

    return entries;
  }

  async function listPageAssets(pageId, options = {}) {
    const preloadOnly = options.preloadOnly === true;
    try {
      const payload = await loadManifest();
      return manifestAssets(payload)
        .filter((asset) => asset?.enabled !== false && assetMatchesPage(asset, pageId))
        .filter((asset) => !preloadOnly || asset?.preload !== false)
        .map((asset) => resolveWithManifest(payload, pageId, asset.path || "", {}));
    } catch (_error) {
      return [];
    }
  }

  async function getMeta() {
    try {
      const payload = await loadManifest();
      return payload?.manifest?.meta || {};
    } catch (_error) {
      return {};
    }
  }

  window.AssetManifestRuntime = {
    buildPreloadEntries,
    canonicalAssetPath,
    configure,
    getMeta,
    listPageAssets,
    loadManifest,
    resolveAsset
  };

  if (window.ASSET_MANIFEST_RUNTIME_BOOT) {
    configure(window.ASSET_MANIFEST_RUNTIME_BOOT);
  }
})();
