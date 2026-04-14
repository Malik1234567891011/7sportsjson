import puppeteer from "puppeteer";
import fs from "fs";
import path from "path";
import { fileURLToPath } from "url";

const __dirname = path.dirname(fileURLToPath(import.meta.url));

const BASE = "https://www.qidigo.com";
/** Public API (same as the site’s XHR) — used so we get every activity page, not just the first 10 in the DOM. */
const QIDIGO_API = "https://api.qidigo.com/api/v1";

/** Single activity page (only groups for that activity — often 2–5 rows). */
const DEFAULT_ACTIVITY_URL =
  process.env.QIDIGO_URL ||
  "https://www.qidigo.com/u/7-Sports-Estrie/activity/31960";

/**
 * Listing pages: no cards on-page; they link to many /activity/ID pages.
 * Comma-separated in env QIDIGO_LISTING_URLS, or use --full for defaults.
 */
/** Same pattern as the links at the bottom of qidigoscraper.md + Estrie org listing. */
const DEFAULT_LISTING_URLS = [
  "https://www.qidigo.com/u/7SPORTSMM/activities/session",
  "https://www.qidigo.com/u/7SPORTSO/activities/session",
  "https://www.qidigo.com/u/7-Sports-Estrie/activities/session",
];

function activityBaseFromHref(href) {
  if (!href || typeof href !== "string") return null;
  try {
    const u = new URL(href, BASE);
    const m = u.pathname.match(/^(\/u\/[^/]+\/activity\/\d+)/i);
    if (!m) return null;
    return u.origin + m[1];
  } catch {
    return null;
  }
}

const ACTIVITIES_PER_PAGE = Math.min(
  100,
  Math.max(10, Number(process.env.QIDIGO_ACTIVITIES_PER_PAGE || 50))
);

function orgSlugFromListingUrl(listingUrl) {
  const m = String(listingUrl).match(/\/u\/([^/]+)\/activities\//i);
  return m ? m[1] : null;
}

async function fetchOrganizationIdBySlug(slug) {
  const r = await fetch(
    `${QIDIGO_API}/organizations?slug=${encodeURIComponent(slug)}`,
    { headers: { Accept: "application/json" } }
  );
  if (!r.ok) return null;
  const data = await r.json();
  return Array.isArray(data) && data[0]?.id != null ? data[0].id : null;
}

/**
 * All activity URLs for an org (paginates until the API returns a short page).
 * The website’s ?page=3 query is stripped by the SPA; the real source is this API.
 */
async function fetchActivityUrlsForOrgSlug(slug) {
  const orgId = await fetchOrganizationIdBySlug(slug);
  if (!orgId) {
    console.error(`  API: no organization for slug "${slug}"`);
    return [];
  }

  const urls = [];
  let page = 1;
  let totalReported = null;

  for (;;) {
    const apiUrl = `${QIDIGO_API}/organizations/${orgId}/activities?activities_per_page=${ACTIVITIES_PER_PAGE}&page=${page}`;
    const r = await fetch(apiUrl, { headers: { Accept: "application/json" } });
    if (!r.ok) {
      console.error(`  ${slug} API page ${page}: HTTP ${r.status}`);
      break;
    }
    const data = await r.json();
    const items = data.items || [];
    if (totalReported === null && typeof data.total_size === "number") {
      totalReported = data.total_size;
    }
    for (const it of items) {
      if (it?.id != null) {
        urls.push(`${BASE}/u/${slug}/activity/${it.id}`);
      }
    }
    console.error(
      `  ${slug}  API page ${page}: +${items.length} activities (running total ${urls.length}${totalReported != null ? ` / ${totalReported} reported` : ""})`
    );
    if (items.length === 0) break;
    if (items.length < ACTIVITIES_PER_PAGE) break;
    page++;
    if (page > 500) {
      console.error("  (stopped: page cap 500)");
      break;
    }
  }

  return urls;
}

async function discoverActivityUrlsForListing(listingUrl) {
  const slug = orgSlugFromListingUrl(listingUrl);
  if (!slug) {
    console.error("  Could not parse org slug from:", listingUrl);
    return [];
  }
  return fetchActivityUrlsForOrgSlug(slug);
}

async function scrapeFragmentsFromPage(page) {
  return page.evaluate(
    ({ base }) => {
      const getText = (root, selector) =>
        root.querySelector(selector)?.innerText?.trim() || "";

      const getAllText = (root, selector) =>
        Array.from(root.querySelectorAll(selector))
          .map((e) => e.innerText.trim())
          .filter(Boolean)
          .join(" ");

      return Array.from(
        document.querySelectorAll(".activity-group-fragment")
      ).map((el) => {
        const title = getText(el, "h3");

        const imageEl = el.querySelector("img");
        let image = "";
        if (imageEl) {
          const src =
            imageEl.getAttribute("src") ||
            imageEl.getAttribute("data-src") ||
            "";
          image = src.startsWith("http")
            ? src
            : src.startsWith("//")
              ? "https:" + src
              : base + (src.startsWith("/") ? src : "/" + src);
        }

        const linkEl = el.querySelector("a[href]");
        let link = "";
        if (linkEl) {
          const href = linkEl.getAttribute("href") || "";
          link = href.startsWith("http")
            ? href
            : href.startsWith("//")
              ? "https:" + href
              : base + (href.startsWith("/") ? href : "/" + href);
        }

        const price = getText(el, ".money-amount");

        const schedule = getAllText(el, ".schedulefragment li");
        const duration = getAllText(el, ".durationfragment li");

        let age = "";
        let remaining_spots = "";

        const infoItems = Array.from(
          el.querySelectorAll(".activity-group-fragment--informations li")
        );

        for (const item of infoItems) {
          const raw = item.innerText.trim();
          const lower = raw.toLowerCase();
          if (/\bage\b|âge/i.test(raw)) {
            age = raw.replace(/^\s*(age|âge)\s*:?\s*/i, "").trim();
          }
          if (
            /remaining|restant|places?\s+disponibles?|spots?/i.test(lower)
          ) {
            remaining_spots = raw
              .replace(
                /^\s*(remaining\s+places?|places?\s+restantes?|spots?\s+restante?s?)\s*:?\s*/i,
                ""
              )
              .trim();
          }
        }

        return {
          title,
          image,
          link,
          price,
          schedule,
          duration,
          age,
          remaining_spots,
        };
      });
    },
    { base: BASE }
  );
}

async function scrapeActivityPage(page, url) {
  await page.goto(url, { waitUntil: "networkidle2", timeout: 120_000 });
  try {
    await page.waitForSelector(".activity-group-fragment", {
      timeout: 45_000,
    });
  } catch {
    return [];
  }
  return scrapeFragmentsFromPage(page);
}

function dedupeByLink(programs) {
  const seen = new Set();
  return programs.filter((p) => {
    const k = p.link || p.title;
    if (!k || seen.has(k)) return false;
    seen.add(k);
    return true;
  });
}

function norm(s) {
  if (s == null) return "";
  return String(s).replace(/\s+/g, " ").trim();
}

function stripAccents(s) {
  return String(s)
    .normalize("NFD")
    .replace(/\p{M}/gu, "");
}

/** First pipe segment when it is a season keyword → normalized English; else null. */
function seasonFromTitleSegment(segment) {
  const key = stripAccents(norm(segment))
    .toUpperCase()
    .replace(/\s+/g, " ")
    .trim();
  const map = {
    ETE: "summer",
    HIVER: "winter",
    PRINTEMPS: "spring",
    AUTOMNE: "fall",
  };
  return map[key] ?? null;
}

/**
 * Infer season from duration text (English/French month names). Uses first month found.
 */
function inferSeasonFromDuration(duration) {
  const raw = norm(duration);
  if (!raw) return null;
  const d = stripAccents(raw).toLowerCase();
  const monthOrder = [
    ["january", "winter"],
    ["february", "winter"],
    ["march", "spring"],
    ["april", "spring"],
    ["may", "spring"],
    ["june", "summer"],
    ["july", "summer"],
    ["august", "summer"],
    ["september", "fall"],
    ["october", "fall"],
    ["november", "fall"],
    ["december", "winter"],
    ["janvier", "winter"],
    ["fevrier", "winter"],
    ["mars", "spring"],
    ["avril", "spring"],
    ["mai", "spring"],
    ["juin", "summer"],
    ["juillet", "summer"],
    ["aout", "summer"],
    ["septembre", "fall"],
    ["octobre", "fall"],
    ["novembre", "fall"],
    ["decembre", "winter"],
  ];
  for (const [month, season] of monthOrder) {
    if (d.includes(month)) return season;
  }
  return null;
}

function resolveSeason(seasonFromTitle, duration) {
  if (seasonFromTitle) return seasonFromTitle;
  return inferSeasonFromDuration(duration);
}

/** Title-case place names (hyphens/spaces preserved). */
function titleCasePlace(s) {
  const t = norm(s);
  if (!t) return "";
  return t
    .toLowerCase()
    .split(/(\s+|[-'])/)
    .map((part) => {
      if (/^[\s'-]+$/.test(part) || part === "") return part;
      return part.charAt(0).toUpperCase() + part.slice(1);
    })
    .join("");
}

function buildFullAddress(addr) {
  if (!addr || typeof addr !== "object") return "";
  const parts = [
    norm(addr.street),
    norm(addr.city),
    norm(addr.region),
    norm(addr.postal_code),
  ].filter(Boolean);
  return parts.join(", ");
}

const NOMINATIM_DELAY_MS = 700;
const NOMINATIM_USER_AGENT =
  "Qidigo7SportsProgramScraper/1.0 (education programs geocoding; contact via org website)";

function sleep(ms) {
  return new Promise((r) => setTimeout(r, ms));
}

/** Canadian postal code: A1A 1A1 (no D,F,I,O,Q,U,Z in first position set — simplified check). */
function compactCanadianPostal(raw) {
  return String(raw || "")
    .replace(/\s+/g, "")
    .toUpperCase();
}

function isValidCanadianPostal(raw) {
  const c = compactCanadianPostal(raw);
  return /^[ABCEGHJ-NPRSTVXY]\d[ABCEGHJ-NPRSTV-Z]\d[ABCEGHJ-NPRSTV-Z]\d$/.test(c);
}

/** Returns formatted "A1A 1A1" or "" if invalid / incomplete (fixes bad scrapes like "J4P 3G"). */
function formatCanadianPostal(raw) {
  const c = compactCanadianPostal(raw);
  if (!/^[ABCEGHJ-NPRSTVXY]\d[ABCEGHJ-NPRSTV-Z]\d[ABCEGHJ-NPRSTV-Z]\d$/.test(c)) {
    return "";
  }
  return `${c.slice(0, 3)} ${c.slice(3)}`;
}

function expandStreetForGeocode(street) {
  let s = norm(street);
  s = s.replace(/\bChem\.\s*/gi, "Chemin ").replace(/\bCh\.\s*/gi, "Chemin ");
  return s.replace(/\s+/g, " ").trim();
}

/**
 * Venue-style string from Qidigo location_name: "Place - City - …" → "Place, City, QC, Canada"
 */
function queryFromLocationName(locationName, city, region) {
  const loc = norm(locationName);
  const c = norm(city);
  const r = norm(region) || "QC";
  if (!loc || !c) return "";
  const parts = loc.split(/\s*-\s*/).map((x) => norm(x)).filter(Boolean);
  if (parts.length >= 2) {
    const second = parts[1].toLowerCase();
    if (second === c.toLowerCase() || c.toLowerCase().includes(second) || second.includes(c.toLowerCase())) {
      return `${parts[0]}, ${c}, ${r}, Canada`;
    }
  }
  if (parts[0]) return `${parts[0]}, ${c}, ${r}, Canada`;
  return "";
}

/**
 * Ordered Nominatim strategies: structured search, then free-text (with/without bad postal, city-only, etc.).
 * @returns {Array<{ kind: 'structured', params: Record<string, string> } | { kind: 'query', q: string }>}
 */
function buildGeocodeCandidates(addr, locationName) {
  const streetRaw = norm(addr?.street);
  const street = expandStreetForGeocode(streetRaw);
  const city = norm(addr?.city);
  const region = norm(addr?.region) || "QC";
  const postal = formatCanadianPostal(addr?.postal_code);
  const postalRaw = norm(addr?.postal_code);

  const list = [];
  const seen = new Set();

  const addStructured = (params) => {
    if (!params.city) return;
    const key = `s|${norm(params.street)}|${params.city}|${norm(params.state)}|${norm(params.postalcode || "")}`;
    if (seen.has(key)) return;
    seen.add(key);
    list.push({ kind: "structured", params });
  };

  const addQuery = (q) => {
    const t = norm(q);
    if (!t) return;
    if (seen.has(`q|${t}`)) return;
    seen.add(`q|${t}`);
    list.push({ kind: "query", q: t });
  };

  if (city) {
    addStructured({
      ...(street ? { street } : {}),
      city,
      state: region,
      country: "Canada",
      ...(postal ? { postalcode: postal } : {}),
    });
    if (street) {
      addStructured({
        street,
        city,
        state: region,
        country: "Canada",
      });
    }
  }

  if (street && city) {
    addQuery(`${street}, ${city}, ${region}${postal ? `, ${postal}` : ""}, Canada`);
    addQuery(`${street}, ${city}, Quebec, Canada`);
    if (postalRaw && !postal) {
      addQuery(`${street}, ${city}, ${region}, Canada`);
    }
  }
  if (city) {
    addQuery(`${city}, ${region}, Canada`);
    addQuery(`${city}, Quebec, Canada`);
  }
  if (postal) {
    addQuery(`${postal}, Canada`);
  }

  const fsa = compactCanadianPostal(postalRaw);
  if (!postal && fsa.length >= 3 && city) {
    const head = fsa.slice(0, 3);
    if (/^[ABCEGHJ-NPRSTVXY]\d[ABCEGHJ-NPRSTV-Z]$/i.test(head)) {
      addQuery(`${city}, ${head}, QC, Canada`);
    }
  }

  const venueQ = queryFromLocationName(locationName, city, region);
  if (venueQ) addQuery(venueQ);

  return list;
}

function candidateCacheKey(c) {
  if (c.kind === "structured") {
    const p = c.params;
    return `s|${norm(p.street)}|${p.city}|${norm(p.state)}|${norm(p.postalcode || "")}`;
  }
  return `q|${c.q}`;
}

function parseNominatimFirst(data) {
  if (!Array.isArray(data) || !data[0]) {
    return { latitude: null, longitude: null };
  }
  const lat = parseFloat(data[0].lat);
  const lon = parseFloat(data[0].lon);
  return {
    latitude: Number.isNaN(lat) ? null : lat,
    longitude: Number.isNaN(lon) ? null : lon,
  };
}

/**
 * One Nominatim request; caches by candidate key; always delays after HTTP (policy + rate limit).
 * @param {Map<string, { latitude: number | null, longitude: number | null }>} geoCache
 */
async function nominatimFetchCandidate(c, geoCache) {
  const ck = candidateCacheKey(c);
  if (geoCache.has(ck)) {
    return geoCache.get(ck);
  }

  let latitude = null;
  let longitude = null;
  try {
    let url;
    if (c.kind === "structured") {
      const p = new URLSearchParams();
      p.set("format", "json");
      p.set("limit", "1");
      p.set("countrycodes", "ca");
      if (c.params.street) p.set("street", c.params.street);
      p.set("city", c.params.city);
      p.set("state", c.params.state || "QC");
      p.set("country", c.params.country || "Canada");
      if (c.params.postalcode) p.set("postalcode", c.params.postalcode);
      url = `https://nominatim.openstreetmap.org/search?${p.toString()}`;
    } else {
      const q = encodeURIComponent(c.q);
      url = `https://nominatim.openstreetmap.org/search?q=${q}&format=json&limit=1&countrycodes=ca`;
    }
    const r = await fetch(url, {
      headers: {
        "User-Agent": NOMINATIM_USER_AGENT,
        Accept: "application/json",
      },
    });
    if (r.ok) {
      const data = await r.json();
      ({ latitude, longitude } = parseNominatimFirst(data));
    }
  } catch {
    /* leave null */
  }

  const result = { latitude, longitude };
  geoCache.set(ck, result);
  await sleep(NOMINATIM_DELAY_MS);
  return result;
}

/**
 * Try several queries per program; memo full_address → first successful coords.
 * Handles invalid/truncated postal codes from the site (common cause of Nominatim misses).
 */
async function geocodeProgramRow(program, geoCache) {
  const addr = program.address && typeof program.address === "object" ? program.address : {};
  const primaryKey = norm(program.full_address || buildFullAddress(addr));
  if (!primaryKey && !norm(addr.city)) {
    const empty = { latitude: null, longitude: null };
    return empty;
  }
  const memoKey = `prog|${primaryKey || `${norm(addr.street)}|${norm(addr.city)}`}`;
  if (geoCache.has(memoKey)) {
    return geoCache.get(memoKey);
  }

  const candidates = buildGeocodeCandidates(addr, program.location_name);
  if (candidates.length === 0) {
    const empty = { latitude: null, longitude: null };
    geoCache.set(memoKey, empty);
    return empty;
  }

  for (const c of candidates) {
    const res = await nominatimFetchCandidate(c, geoCache);
    if (res.latitude != null && res.longitude != null) {
      geoCache.set(memoKey, res);
      return res;
    }
  }

  const miss = { latitude: null, longitude: null };
  geoCache.set(memoKey, miss);
  return miss;
}

/**
 * Optional leading season: "ÉTÉ | CITY | SPORT | …" → city/sport/age shift by one.
 * Without season: "BELOEIL | MULTISPORT | 2 - 4 ans | …" unchanged.
 */
function parseFromTitle(title) {
  const t = norm(title);
  const parts = t.split("|").map((x) => norm(x));
  let seasonFromTitle = null;
  let offset = 0;
  if (parts.length > 0 && parts[0]) {
    seasonFromTitle = seasonFromTitleSegment(parts[0]);
    if (seasonFromTitle) offset = 1;
  }
  const city = parts[offset] || "";
  const sport = parts[offset + 1] || "";
  let age_range = parts[offset + 2] || "";
  age_range = age_range.replace(/\bans\b/gi, "").replace(/\s+/g, " ").trim();
  return { city, sport, age_range, seasonFromTitle };
}

function contactUrlFromGroupLink(link) {
  const base = norm(link).replace(/\/$/, "");
  if (!base) return "";
  return `${base}/contact`;
}

function throttleContactDelay() {
  const ms = 300 + Math.random() * 500;
  return new Promise((r) => setTimeout(r, ms));
}

/**
 * Step 2: group registration link + /contact → hCard fields
 */
async function scrapeContactPage(page, groupLink) {
  const url = contactUrlFromGroupLink(groupLink);
  const empty = {
    location_name: "",
    street: "",
    city: "",
    region: "",
    postal_code: "",
  };
  if (!url) return empty;

  try {
    await page.goto(url, { waitUntil: "networkidle2", timeout: 90_000 });
    await new Promise((r) => setTimeout(r, 400));
    const data = await page.evaluate(() => {
      const streetEl = document.querySelector(".p-street-address");
      let location_name = "";
      if (streetEl) {
        const block =
          streetEl.closest(
            ".vcard, .adr, .h-card, .location, article, section"
          ) || streetEl.parentElement?.parentElement;
        if (block) {
          const h3 = block.querySelector("h3");
          location_name = h3?.innerText?.trim() || "";
        }
      }
      if (!location_name) {
        const first = document.querySelector("main h3, .content h3, h3");
        location_name = first?.innerText?.trim() || "";
      }
      return {
        location_name,
        street:
          document.querySelector(".p-street-address")?.innerText?.trim() || "",
        city: document.querySelector(".p-locality")?.innerText?.trim() || "",
        region: document.querySelector(".p-region")?.innerText?.trim() || "",
        postal_code:
          document.querySelector(".p-postal-code")?.innerText?.trim() || "",
      };
    });
    return {
      location_name: norm(data.location_name),
      street: norm(data.street),
      city: norm(data.city),
      region: norm(data.region),
      postal_code: norm(data.postal_code),
    };
  } catch {
    return { ...empty };
  }
}

/**
 * Group page (not /contact) often has "Essai Gratuit …" with an external ticket link in .is-formatted-text.
 * React may hydrate after networkidle — wait for the group description block, not the first .is-formatted-text on the page.
 */
async function scrapeGroupPageTrialLink(page, groupLink) {
  const url = norm(groupLink).replace(/\/$/, "");
  if (!url) return "";

  try {
    await page.setExtraHTTPHeaders({
      "Accept-Language": "fr-CA,fr;q=0.9,en-CA,en;q=0.8",
    });
    await page.goto(url, { waitUntil: "networkidle2", timeout: 90_000 });
    await page
      .waitForSelector(".group-header--description", { timeout: 15_000 })
      .catch(() => {});
    await page
      .waitForFunction(
        () => {
          const box = document.querySelector(
            ".group-header--description .is-formatted-text"
          );
          if (!box) return false;
          const t = (box.textContent || "").replace(/\s+/g, " ");
          return (
            t.length > 40 &&
            (/essai/i.test(t) || /free\s*trial/i.test(t) || /gratuit/i.test(t))
          );
        },
        { timeout: 20_000 }
      )
      .catch(() => {});
    await new Promise((r) => setTimeout(r, 800));
    const href = await page.evaluate(() => {
      const trialRe =
        /essai\s*gratuit|essai\s*gratuits|free\s*trial|trial\s*gratuit|gratuit.*essai|essai\s+gratuit/i;

      function resolveHref(a) {
        const raw = a.getAttribute("href");
        if (!raw || raw.startsWith("#") || raw.startsWith("mailto:")) return "";
        try {
          const abs = new URL(raw, window.location.href).href;
          if (!/^https?:\/\//i.test(abs) || /qidigo\.com/i.test(abs)) return "";
          return String(abs).trim();
        } catch {
          return "";
        }
      }

      const roots = document.querySelectorAll(
        ".group-header--description .is-formatted-text"
      );
      const rootList =
        roots.length > 0
          ? Array.from(roots)
          : [
              document.querySelector(".group-header--description") ||
                document.querySelector(".page-group") ||
                document.body,
            ].filter(Boolean);

      for (const root of rootList) {
        if (!root) continue;
        for (const p of root.querySelectorAll("p")) {
          const t = (p.textContent || "").replace(/\s+/g, " ");
          if (!trialRe.test(t)) continue;
          for (const a of p.querySelectorAll("a[href]")) {
            const u = resolveHref(a);
            if (u) return u;
          }
        }

        for (const a of root.querySelectorAll("a[href]")) {
          const u = resolveHref(a);
          if (!u) continue;
          let block = a.closest("p, li, .is-formatted-text") || a.parentElement;
          let blob = "";
          for (let d = 0; d < 8 && block; d++) {
            blob += block.textContent || "";
            block = block.parentElement;
          }
          if (trialRe.test(blob.replace(/\s+/g, " "))) return u;
        }
      }

      const html = document.documentElement.innerHTML || "";
      const flat = html.replace(/\s+/g, " ");
      if (trialRe.test(flat)) {
        const m = flat.match(
          /https:\/\/app\.eventnroll\.com[^"'\\s&<>]*/i
        );
        if (m) return m[0];
      }
      return "";
    });
    return norm(href);
  } catch {
    return "";
  }
}

function buildFinalProgram(raw, contact, titleParsed, trialLink = "") {
  const addr = {
    street: norm(contact.street),
    city: norm(contact.city),
    region: norm(contact.region),
    postal_code: norm(contact.postal_code),
  };
  const city =
    addr.city !== ""
      ? titleCasePlace(addr.city)
      : titleCasePlace(titleParsed.city);
  const sport = norm(titleParsed.sport);
  const age_range = norm(titleParsed.age_range);
  const duration = norm(raw.duration);
  const season = resolveSeason(titleParsed.seasonFromTitle, duration) ?? null;
  const full_address = buildFullAddress(addr);

  return {
    title: norm(raw.title),
    city,
    sport,
    age_range,
    price: norm(raw.price),
    schedule: norm(raw.schedule),
    duration,
    age: norm(raw.age),
    remaining_spots: norm(raw.remaining_spots),
    link: norm(raw.link),
    location_name: norm(contact.location_name),
    address: addr,
    season,
    full_address,
    trial_link: norm(trialLink),
  };
}

/**
 * Adds latitude / longitude via Nominatim (cached by full_address, ~700ms between API calls).
 */
async function enrichProgramsWithGeocoding(programs, options = {}) {
  const { onlyMissing = false } = options;
  const geoCache = new Map();
  const n = programs.length;
  for (let i = 0; i < n; i++) {
    if (
      onlyMissing &&
      programs[i].latitude != null &&
      programs[i].longitude != null
    ) {
      continue;
    }
    const { latitude, longitude } = await geocodeProgramRow(programs[i], geoCache);
    programs[i].latitude = latitude;
    programs[i].longitude = longitude;
    if (i % 25 === 0 || i === n - 1) {
      process.stderr.write(`  geocode ${i + 1}/${n}\n`);
    }
  }
  return programs;
}

async function geocodeJsonFileOnly() {
  const outPath = path.join(__dirname, "programs.json");
  if (!fs.existsSync(outPath)) {
    console.error("Missing programs.json at", outPath);
    process.exit(1);
  }
  const programs = JSON.parse(fs.readFileSync(outPath, "utf8"));
  if (!Array.isArray(programs)) {
    console.error("programs.json must be a JSON array");
    process.exit(1);
  }
  const missing = programs.filter(
    (p) => p == null || p.latitude == null || p.longitude == null
  ).length;
  console.error(
    `Re-geocoding ${missing} row(s) with missing coordinates (skipping rows that already have lat/lng)…`
  );
  await enrichProgramsWithGeocoding(programs, { onlyMissing: true });
  fs.writeFileSync(outPath, JSON.stringify(programs, null, 2), "utf8");
  console.log("Updated:", outPath);
}

function programNeedsTrialRefill(p) {
  if (!p || typeof p !== "object") return false;
  const t = p.trial_link;
  return t == null || (typeof t === "string" && norm(t) === "");
}

/**
 * Fetches trial_link from each program's Qidigo group page (Puppeteer).
 * @param {{ onlyMissing?: boolean, linkFilter?: string }} options
 * Env QIDIGO_TRIALS_LINK_FILTER: substring of `link` — only those rows are fetched (quick test, e.g. group id 445348).
 */
async function enrichProgramsWithTrials(page, programs, options = {}) {
  const { onlyMissing = true, linkFilter: linkFilterOpt } = options;
  const linkFilter = norm(
    linkFilterOpt ?? process.env.QIDIGO_TRIALS_LINK_FILTER ?? ""
  );
  const n = programs.length;
  for (let i = 0; i < n; i++) {
    if (onlyMissing && !programNeedsTrialRefill(programs[i])) {
      continue;
    }
    const link = programs[i].link;
    if (!norm(link)) {
      programs[i].trial_link = programs[i].trial_link ?? "";
      continue;
    }
    if (
      linkFilter !== "" &&
      !String(link).toLowerCase().includes(linkFilter.toLowerCase())
    ) {
      continue;
    }
    process.stderr.write(
      `  trial ${i + 1}/${n} ${String(link).slice(-50) || ""}\n`
    );
    await throttleContactDelay();
    let trialLink = "";
    try {
      trialLink = await scrapeGroupPageTrialLink(page, link);
    } catch {
      trialLink = "";
    }
    programs[i].trial_link = norm(trialLink);
    if (i % 25 === 0 || i === n - 1) {
      process.stderr.write(`  trials progress ${i + 1}/${n}\n`);
    }
  }
  return programs;
}

async function trialsJsonFileOnly() {
  const outPath = path.join(__dirname, "programs.json");
  if (!fs.existsSync(outPath)) {
    console.error("Missing programs.json at", outPath);
    process.exit(1);
  }
  const programs = JSON.parse(fs.readFileSync(outPath, "utf8"));
  if (!Array.isArray(programs)) {
    console.error("programs.json must be a JSON array");
    process.exit(1);
  }
  const linkFilter = norm(process.env.QIDIGO_TRIALS_LINK_FILTER || "");
  const need = programs.filter((p) => {
    if (!programNeedsTrialRefill(p)) return false;
    if (linkFilter === "") return true;
    const lk = String(p?.link || "").toLowerCase();
    return lk.includes(linkFilter.toLowerCase());
  }).length;
  console.error(
    linkFilter !== ""
      ? `Fetching trial_link for ${need} row(s) matching link filter "${linkFilter}"…`
      : `Fetching trial_link for ${need} row(s) (skipping non-empty trial_link)…`
  );
  const browser = await puppeteer.launch({
    headless: true,
    args: ["--no-sandbox", "--disable-setuid-sandbox"],
  });
  const page = await browser.newPage();
  await page.setViewport({ width: 1280, height: 900 });
  try {
    await enrichProgramsWithTrials(page, programs, { onlyMissing: true });
  } finally {
    await browser.close();
  }
  fs.writeFileSync(outPath, JSON.stringify(programs, null, 2), "utf8");
  console.log("Updated:", outPath);
}

async function enrichProgramsWithContactPages(page, programsRaw) {
  const out = [];
  for (let i = 0; i < programsRaw.length; i++) {
    process.stderr.write(
      `  contact ${i + 1}/${programsRaw.length} ${programsRaw[i].link?.slice(-40) || ""}\n`
    );
    await throttleContactDelay();
    let trialLink = "";
    try {
      trialLink = await scrapeGroupPageTrialLink(page, programsRaw[i].link);
    } catch {
      trialLink = "";
    }
    await throttleContactDelay();
    let contact;
    try {
      contact = await scrapeContactPage(page, programsRaw[i].link);
    } catch {
      contact = {
        location_name: "",
        street: "",
        city: "",
        region: "",
        postal_code: "",
      };
    }
    const parsed = parseFromTitle(programsRaw[i].title);
    out.push(buildFinalProgram(programsRaw[i], contact, parsed, trialLink));
  }
  return out;
}

async function scrapeQidigo(options = {}) {
  const full =
    options.full ||
    process.env.QIDIGO_FULL === "1" ||
    process.argv.includes("--full");

  if (full) {
    const listingUrls = process.env.QIDIGO_LISTING_URLS
      ? process.env.QIDIGO_LISTING_URLS.split(/[,\s]+/).filter(Boolean)
      : [...DEFAULT_LISTING_URLS];

    const discovered = new Set();
    if (process.env.QIDIGO_URL) {
      discovered.add(
        (
          activityBaseFromHref(process.env.QIDIGO_URL) || process.env.QIDIGO_URL
        ).replace(/\/$/, "")
      );
    }

    for (const listUrl of listingUrls) {
      console.error("Discovering activities (Qidigo API):", listUrl);
      const found = await discoverActivityUrlsForListing(listUrl);
      found.forEach((u) => discovered.add(u.replace(/\/$/, "")));
    }

    const browser = await puppeteer.launch({
      headless: true,
      args: ["--no-sandbox", "--disable-setuid-sandbox"],
    });
    const page = await browser.newPage();
    await page.setViewport({ width: 1280, height: 900 });

    const activityUrls = [...discovered];
    console.error("Total unique activity pages to scrape:", activityUrls.length);

    const all = [];
    for (let i = 0; i < activityUrls.length; i++) {
      const u = activityUrls[i];
      process.stderr.write(`[${i + 1}/${activityUrls.length}] ${u}\n`);
      try {
        const rows = await scrapeActivityPage(page, u);
        process.stderr.write(`  -> ${rows.length} group(s)\n`);
        all.push(...rows);
      } catch (err) {
        process.stderr.write(`  -> SKIP (error: ${err.message?.slice(0, 80)})\n`);
      }
    }

    const programsRaw = dedupeByLink(all);
    console.error(
      `Enriching ${programsRaw.length} programs (contact pages, throttled)…`
    );
    let programs = await enrichProgramsWithContactPages(page, programsRaw);
    await browser.close();

    console.error(`Geocoding ${programs.length} programs (Nominatim, cached, throttled)…`);
    programs = await enrichProgramsWithGeocoding(programs);

    const outPath = path.join(__dirname, "programs.json");
    fs.writeFileSync(outPath, JSON.stringify(programs, null, 2), "utf8");
    console.log("Mode: full (API + activity pages + contact enrich)");
    console.log("Activity pages:", activityUrls.length);
    console.log("Program rows (groups):", programs.length);
    console.log("Wrote:", outPath);
    if (programs[0])
      console.log("Sample:", JSON.stringify(programs[0], null, 2));
    return programs;
  }

  const url = process.env.QIDIGO_URL || DEFAULT_ACTIVITY_URL;
  const browser = await puppeteer.launch({
    headless: true,
    args: ["--no-sandbox", "--disable-setuid-sandbox"],
  });
  const page = await browser.newPage();
  await page.setViewport({ width: 1280, height: 900 });

  const programsRaw = dedupeByLink(await scrapeActivityPage(page, url));
  console.error(
    `Enriching ${programsRaw.length} programs (contact pages, throttled)…`
  );
  let programs = await enrichProgramsWithContactPages(page, programsRaw);
  await browser.close();

  console.error(`Geocoding ${programs.length} programs (Nominatim, cached, throttled)…`);
  programs = await enrichProgramsWithGeocoding(programs);

  const outPath = path.join(__dirname, "programs.json");
  fs.writeFileSync(outPath, JSON.stringify(programs, null, 2), "utf8");

  console.log("URL:", url);
  console.log("Programs:", programs.length);
  console.log(
    "(This URL is ONE activity; each age/time slot is a row. Use: npm run scrape:full for all listing activities.)"
  );
  console.log("Wrote:", outPath);
  if (programs[0]) console.log("Sample:", JSON.stringify(programs[0], null, 2));

  return programs;
}

async function trialUrlSmokeTest() {
  const arg = process.argv.find((a) => a.startsWith("--trial-url="));
  const u = arg
    ? decodeURIComponent(arg.slice("--trial-url=".length).trim())
    : "";
  if (!norm(u)) {
    console.error(
      "Usage: node scraper.js --trial-url=https://www.qidigo.com/u/.../group/..."
    );
    process.exit(1);
  }
  const browser = await puppeteer.launch({
    headless: true,
    args: ["--no-sandbox", "--disable-setuid-sandbox"],
  });
  const page = await browser.newPage();
  await page.setViewport({ width: 1280, height: 900 });
  try {
    const href = await scrapeGroupPageTrialLink(page, u);
    if (href) {
      console.log(href);
    } else {
      console.error("(no trial link detected)");
      process.exitCode = 1;
    }
  } finally {
    await browser.close();
  }
}

const trialUrlArg = process.argv.some((a) => a.startsWith("--trial-url="));
if (trialUrlArg) {
  trialUrlSmokeTest()
    .then(() => process.exit(process.exitCode || 0))
    .catch((err) => {
      console.error(err);
      process.exit(1);
    });
} else if (process.argv.includes("--geocode-json")) {
  geocodeJsonFileOnly()
    .then(() => process.exit(0))
    .catch((err) => {
      console.error(err);
      process.exit(1);
    });
} else if (process.argv.includes("--trials-json")) {
  trialsJsonFileOnly()
    .then(() => process.exit(0))
    .catch((err) => {
      console.error(err);
      process.exit(1);
    });
} else {
  scrapeQidigo().catch((err) => {
    console.error(err);
    process.exit(1);
  });
}
