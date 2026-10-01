(function () {
  const track = document.getElementById("homeSliderTrack");
  const prevBtn = document.getElementById("homeSliderPrev");
  const nextBtn = document.getElementById("homeSliderNext");

  if (!track) return;

  function escapeHtml(str) {
    return String(str ?? "")
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  }

  function formatPrice(listing) {
    if (listing.pricePerKg === null || listing.pricePerKg === undefined) {
      return listing.priceNote ? listing.priceNote : "Ζητήστε τιμή";
    }

    const n = Number(listing.pricePerKg);
    if (Number.isNaN(n)) return "Ζητήστε τιμή";
    return `€${n.toFixed(2)} / kg`;
  }

  function formatDateGR(dateStr) {
    if (!dateStr) return "—";
    const parts = String(dateStr).split("-");
    if (parts.length !== 3) return dateStr;
    const [year, month, day] = parts;
    return `${day}-${month}-${year}`;
  }

  function formatHarvestMonth(ym) {
    if (!ym || !String(ym).includes("-")) return "";
    const [y, m] = String(ym).split("-");
    return `${m}-${y}`;
  }

  async function loadLabels() {
    const [cropsRes, regionsRes, varietiesRes] = await Promise.all([
      fetch("/data/crops.json"),
      fetch("/data/regions.json"),
      fetch("/data/varietyByCrop.json").catch(() => null),
    ]);

    const cropsJson = await cropsRes.json();
    const regionsJson = await regionsRes.json();
    const varietiesJson = varietiesRes ? await varietiesRes.json().catch(() => ({})) : {};

    const cropMap = new Map();
    for (const group of cropsJson?.groups || []) {
      for (const item of group.items || []) {
        if (item?.value && item?.label) cropMap.set(item.value, item.label);
      }
    }

    const regionMap = new Map();
    for (const group of regionsJson?.groups || []) {
      for (const item of group.items || []) {
        if (item?.value && item?.label) regionMap.set(item.value, item.label);
      }
    }

    return { cropMap, regionMap, varietiesJson };
  }

  function resolveVarietyLabel(varietiesJson, cropValue, varietyValue) {
    if (!varietyValue) return "";
    const arr = varietiesJson?.[cropValue];
    if (!Array.isArray(arr)) return "";
    const found = arr.find(v => v.value === varietyValue);
    return found?.label || "";
  }

  function renderListings(items, maps) {
    if (!items.length) {
      track.innerHTML = `<div class="home-slider-empty">Δεν υπάρχουν διαθέσιμες αγγελίες ακόμη.</div>`;
      return;
    }

    track.innerHTML = items.map((l) => {
      const cropLabel = maps.cropMap.get(l.cropType) || l.cropType || "Αγγελία";
      const regionLabel = maps.regionMap.get(l.region) || l.region || "—";
      const varietyLabel = resolveVarietyLabel(maps.varietiesJson, l.cropType, l.variety);

      const harvestStart = formatHarvestMonth(l.harvestStart);
      const harvestEnd = formatHarvestMonth(l.harvestEnd);
      const harvestText = harvestStart && harvestEnd
        ? (harvestStart === harvestEnd ? harvestStart : `${harvestStart} → ${harvestEnd}`)
        : "—";

      const image = Array.isArray(l.images) && l.images.length
        ? l.images[0]
        : (l.image || "images/listings/placeholder.webp");

      return `
        <article class="home-slider-card">
          <a class="home-slider-link" href="/listing?id=${encodeURIComponent(l.id)}">
            <img class="home-slider-image" src="${escapeHtml(image)}" alt="${escapeHtml(cropLabel)}" loading="lazy">

            <div class="home-slider-body">
              <div class="home-slider-top">
                <span class="home-slider-chip">${escapeHtml(regionLabel)}</span>
                ${varietyLabel ? `<span class="home-slider-chip light">${escapeHtml(varietyLabel)}</span>` : ""}
              </div>

              <h3 class="home-slider-title">${escapeHtml(cropLabel)}</h3>

              <div class="home-slider-meta">
                <div><strong>Ποσότητα:</strong> ${escapeHtml(l.quantityTons !== undefined ? `${l.quantityTons} τόνοι` : "—")}</div>
                <div><strong>Συγκομιδή:</strong> ${escapeHtml(harvestText)}</div>
                <div><strong>Δημοσίευση:</strong> ${escapeHtml(formatDateGR(l.createdAt))}</div>
              </div>

              <div class="home-slider-price">${escapeHtml(formatPrice(l))}</div>
            </div>
          </a>
        </article>
      `;
    }).join("");
  }

  async function init() {
    try {
      const [maps, listingsRes] = await Promise.all([
        loadLabels(),
        fetch("/api/listings.php"),
      ]);

      if (!listingsRes.ok) throw new Error(`HTTP ${listingsRes.status}`);

      const listings = await listingsRes.json();

      const latest = [...listings]
        .sort((a, b) => (Date.parse(b.createdAt || "") || 0) - (Date.parse(a.createdAt || "") || 0))
        .slice(0, 8);

      renderListings(latest, maps);
    } catch (err) {
      console.error("Failed to load home slider:", err);
      track.innerHTML = `<div class="home-slider-empty">Δεν ήταν δυνατή η φόρτωση των αγγελιών.</div>`;
    }
  }

  function scrollByCard(direction = 1) {
    const card = track.querySelector(".home-slider-card");
    const amount = card ? card.getBoundingClientRect().width + 14 : 320;
    track.scrollBy({ left: amount * direction, behavior: "smooth" });
  }

  prevBtn?.addEventListener("click", () => scrollByCard(-1));
  nextBtn?.addEventListener("click", () => scrollByCard(1));

  document.addEventListener("DOMContentLoaded", init);
})();