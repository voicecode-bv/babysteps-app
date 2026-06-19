# Ads Plan — Innerr

*Werkdocument — juni 2026. App-install advertising voor de NL-markt.*

Zie ook: [`positionering-innerr.md`](positionering-innerr.md) voor de merkpositionering die de copy en wig hieronder bepaalt.

## Context

| | |
|---|---|
| Product | Innerr — privé-album voor het gezin (mobiele app) |
| Doel | App installs |
| Markt | Nederland |
| Stores | iOS App Store + Google Play (beide live) |
| Budget | ~€1.000/mnd (~€33/dag) |
| Kanalen | Apple Search Ads → Google App → Meta (in deze volgorde) |

## De wig (positioneringsgedreven)

Volgorde van overtuigingskracht, afgeleid uit de positioneringsdoc:

1. **Primair — kindgerichte tijdlijn.** Een tijdlijn geordend op de leeftijd van het kind ("6 maanden", "2 jaar en 3 maanden") met mijlpalen. Het sterkste, moeilijkst te kopiëren onderscheid.
2. **Kernbelofte — privacy.** Geen publiek, geen vreemden, geen advertenties, geen AI-training op kindgezichten.
3. **Kernbelofte — familie samen.** Opa en oma kijken mee via een drempelloze invite-link.
4. **Afsluiter — tastbare herinneringen.** De print shop: van moment naar iets tastbaars (prints, ingelijste foto's, puzzels). Geen entree-reden, wel verdieping + gifting-hoek.

**Echte concurrent** (per positioneringsdoc): de WhatsApp-familiegroep en het openbare Instagram — niet andere apps. De app-concurrenten hieronder dienen vooral voor zoekintentie in de App Store.

---

## Fase 0 — Fundering (vóór je geld uitgeeft)

### Tracking & attributie

**Tier 1 — minimaal (launch hiermee, ~0 dev-werk):**

| Kanaal | Ingebouwde tracking | Actie |
|---|---|---|
| Apple Search Ads | Apple Ad Attribution (AdServices) | Automatisch zodra campagnes lopen |
| Google App (Android) | Google Play Install Referrer | Automatisch |
| iOS (Google/Meta) | SKAdNetwork | Automatisch op OS-niveau |

➡️ Genoeg om Apple Search Ads vandaag te lanceren en installs + cost-per-install per keyword te zien. Nog geen zicht op gedrag ná installatie.

**Tier 2 — proper (binnen ~2–4 weken, vóór je Meta opschaalt):**

1. Kies één MMP met gratis/startup-tier: **Singular** (aanbevolen — beste gratis SKAN + ASA-support), AppsFlyer of Adjust.
2. Integreer de SDK. In de NativePHP-app betekent dit een kleine **native plugin** (Android Kotlin + iOS Swift), zelfde patroon als de bestaande `mobile-camera`/badge-plugins. Alternatief: **server-to-server (S2S)** vanuit `innerr-api` — lichter, iets minder nauwkeurig.
3. Definieer events: `install → register → eerste post/herinnering → invite verstuurd`.
4. Koppel de kanalen (Apple/Google/Meta-connectors) in de MMP.
5. Optimalisatie-event: start op **install**; schakel bij ~20–30/wk naar **register** om echte gebruikers te kopen i.p.v. downloads.

⚠️ De native-plugin is de enige engineering-taak hier. Apart te scopen (SDK-keuze, bridge-functies, S2S vs native).

---

## Fase 1 — Launch-volgorde (concentreer, niet spreiden)

Niet alles tegelijk. Volgorde op basis van hoeveel creative elk kanaal nodig heeft:

| Volgorde | Kanaal | Waarom | Startbudget |
|---|---|---|---|
| **1e (nu)** | Apple Search Ads | Hergebruikt de store-listing als creative — vandaag te starten. Hoogste intentie, beste CPI op klein budget. | ~€19–21/dag |
| **2e (~1 wk)** | Google App (UAC) | Sterkste op Android post-ATT. Vraagt tekst + paar images/video. | ~€12–15/dag |
| **3e (later)** | Meta AAC | Meest creative-hongerig (~10 distinct creatives). Toevoegen als de bench bestaat. | ~€10/dag |

---

## Apple Search Ads — NL keyword launch kit

Gebruik **ASA Advanced**. Vier campagnes, elk met een duidelijke taak.

### Campagne 1 — Brand (defensief, goedkoopste installs)
Match: Exact · ~€3/dag · bod ~€0,40
```
innerr
innerr app
inner app
```

### Campagne 2 — Categorie / Generiek (de motor)
Match: Exact · ~€8–10/dag · bod ~€0,70–0,90 met CPA-doel
```
familie album            baby album
familie app              baby foto's delen
herinneringen bewaren    kinderen foto's
foto dagboek             familie foto's delen
privé fotoalbum          momenten bewaren
digitaal dagboek         familie tijdlijn
dagboek app              fotoboek maken
```
Positionerings-additions (kindtijdlijn + privacy-frame):
```
kind tijdlijn
baby mijlpalen bijhouden
kinderfoto's veilig delen
foto's delen zonder social media
privé album kind
```

### Campagne 3 — Concurrent (goedkoop, lagere intentie)
Match: Exact · ~€4/dag · bod ~€0,60
```
backthen        tinybeans       day one dagboek
back then       familyalbum     journey dagboek
backthen app    lifecake        keepy
```
Pauzeer elke term die >3× het CPA-doel draait na ~30–40 taps.

### Campagne 4 — Discovery (vind nieuwe keywords)
Search Match: AAN + Broad · ~€4/dag · bod ~€0,50 · geen handmatige keywords.
Negatives om on-topic te blijven:
```
gratis, spelletjes, kinderspel, behang, verjaardag, agenda
```
Wekelijks: winnende zoektermen naar Campagne 2 (exact) verplaatsen, daarna hier negativen.

**Startbudget totaal: ~€19–21/dag (~€600/mnd)** — laat ruimte voor Google later. NL family/journaling-apps landen meestal op €1,50–4 CPI.

---

## Concurrent-watchlist

| App | Positionering | Prijsmodel | Sterkte | Het gat |
|---|---|---|---|---|
| **BackThen** | "Safe space to save & share your kids' story" | Storage-tiered (gratis 1GB → €5,99/mnd 200GB) | Unlimited original-quality opslag, timelapse, prints | Opslag *is* het product — een kluis, geen gedeeld gezinsleven |
| **Tinybeans** | Dagelijks baby-journal, grootouder-vriendelijk | Premium abonnement | Dagelijkse-moment-gewoonte, grootouderadoptie | Verouderd, US-centric, baby-only framing |
| **FamilyAlbum (Mitene)** | Gratis unlimited familie-album | Gratis + betaalde prints | Gratis unlimited = massa-adoptie | Generiek, advertentie-gevoel, pure media-dump zonder verhaal |

**Gemeenschappelijk blind spot:** allemaal albums/kluizen waar één ouder uploadt en familie passief kijkt. Geen leidt met de leeftijd-geordende kindtijdlijn of tastbare keepsakes.

---

## Concurrent-switch Custom Product Page

Mechaniek: een App Store **Custom Product Page** wisselt **screenshots (met caption-overlays), preview-video en promotekst** — naam/subtitle/icon blijven vast. Unieke URL → koppelen aan ad group van Campagne 3.

**Interne naam:** `ASA-Competitor-Switch`

**Promotekst (≤170 tekens):**
> Het privé-album voor je gezin. Leg de eerste jaren vast, geordend op leeftijd, en deel ze met opa, oma en je naasten. En maak van je mooiste momenten iets tastbaars.

**Screenshot-captions (de bezwaren van een switcher, op volgorde):**
1. **"Een tijdlijn die meegroeit met je kind"** — het onderscheid, eerste frame
2. **"Geordend op leeftijd, met elke mijlpaal"** — moeilijk te kopiëren
3. **"100% privé. Geen vreemden, geen advertenties."** — kernbelofte
4. **"Opa en oma kijken mee, met één tik"** — familie + drempelloze invite
5. **"Van moment naar iets tastbaars om te bewaren"** — print shop, de afsluiter

**Waarom deze volgorde:** een concurrent-zoeker gelooft al in privé-gezinsherinneringen — je herverkoopt de categorie niet, je verkoopt *waarom switchen*. Leid met je edge (tijdlijn), bevestig de table stakes (privacy), sluit af met lage drempel en de tastbare hoek.

---

## SKAN conversion-value model (iOS-attributie)

De Singular-SDK zet na install automatisch een conversion value (`manualSkanConversionManagement = false`) — geen extra native code, alleen dit model in het Singular-dashboard. Modeltype: **Conversion / funnel** (niet Revenue; Innerr heeft geen aankoop-events).

**Events (exact zoals de app ze stuurt):** `sng_complete_registration` (registratie, op onboarding-`intro`) → `sng_content_view` (eerste post) → `invite_sent` (uitnodiging).

**Fine value (0–63), milestone = diepste stap:**
| Fine | Betekenis |
|---|---|
| 0 | Alleen install |
| 1 | Geregistreerd |
| 2 | Eerste post (activatie) |
| 3 | Uitnodiging verstuurd |

**Coarse (low/medium/high) — belangrijkst bij <1k installs/mnd (fine value wordt vaak door Apple's drempel weggehouden):**
| Coarse | Uit fine | Gebruiker bereikte |
|---|---|---|
| low | 0 | Install, niet geregistreerd |
| medium | 1 | Geregistreerd, niet geactiveerd |
| high | 2–3 | Eerste post gemaakt (en/of uitgenodigd) = kwaliteitssignaal |

**Window/lock:** default eerste venster **dag 0–2** (de hele funnel zit in de onboarding-sessie); waarde vergrendelen na `invite_sent` of einde onboarding.

**Gebruik:** Google App + Meta AAC optimaliseren richting **activatie (high)**. Apple Search Ads gebruikt dit NIET (deterministisch via AdServices). Bij dit volume veel coarse + soms null → leun op Apple Search Ads + Singular's gemodelleerde data + de coarse low/med/high.

**SKAdNetworkItems (alleen voor Google/Meta, NIET voor Apple Search Ads):** de *adverteerder*-kant (Innerr wordt geadverteerd), niet ads-tonen. ⚠️ Kan **niet** via `nativephp.json` — de NativePHP Info.plist-injectie ondersteunt geen array-van-dicts en crasht erop. Wire dit zodra je Google/Meta op iOS aanzet, via de **app-Info.plist** of een plugin **post-build hook**. Niet blokkerend voor review of voor Apple Search Ads. De volledige lijst (Singular's 27 partners + Google + Meta) staat hieronder klaar.

<details><summary>SKAdNetworkItems — 30 IDs (klaar om te wiren bij Google/Meta-launch)</summary>

```
cstr6suwn9.skadnetwork   (Google)
v9wttpbfk9.skadnetwork   (Meta)
n38lu8286q.skadnetwork   (Meta)
4fzdc2evr5  23zd986j2c  ydx93a7ass  v72qych5uu  6xzpu9s2p8  mlmmfzh3r3
c6k4g5qg8m  hs6bdukanm  m8dbw4sv7c  w9q455wk68  yclnxrl5pm  4468km3ulz
t38b2kh725  7ug5zh24hu  9t245vhmpl  cad8qz2s3j  44jx6755aq  tl55sbb4fm
2u9pt9hc89  5a6flpkh64  8s468mfl3y  klf5c3l5u5  av6w8kgt66  ppxm28t8ap
44n7hlldy6  6964rsfnh4  3rd42ekr43
```
(de losse codes krijgen elk de suffix `.skadnetwork`)
</details>

---

## Volgende stappen

1. ✅ **MMP-plugin gebouwd & gevalideerd** — `packages/innerr-attribution` (Singular, SKAN-only). Init + funnel-events (register/first_moment/invite) bevestigd op een fysiek toestel; App Store privacy-labels ingevuld.
2. **Singular afronden:** SKAN conversion-model invoeren (hierboven), connectors koppelen (Apple Search Ads / Google / Meta), `SKAdNetworkItems`-lijst in `nativephp.json` (vóór Google/Meta op iOS).
3. **Creative bench bouwen** voor Google/Meta (`/ads dna` → `/ads create` → `/ads generate`).
4. ✅ **Apple Search Ads opgezet** — 4 campagnes (Brand / Categorie / Concurrent / Discovery) aangemaakt; AdServices gelinkt + token geverifieerd op toestel (deterministische ASA-attributie).
5. **Singular Apple Search Ads-connector** koppelen + SKAN conversion-model invoeren.
6. **Android-build** met de plugin (zit nog niet in de Android-binary).

---

## Wekelijks beheer (Apple Search Ads)

**Stap 0 — doel-CPI:** kies een doel-kosten-per-install (NL familie-/dagboek-apps: **€2–4** als start). Alles draait om dit getal + de 3×-regel.

**Vast moment, ~15 min/week:**

| # | Wat | Actie |
|---|---|---|
| 1 | 3×-kill-regel | Pauzeer elk keyword/campagne met **CPI > 3× doel** (doel €3 → pauzeer >€9). |
| 2 | Discovery harvesten | Zoektermen-rapport → winnaars als **Exact** naar Categorie, daarna als **negative** terug in Discovery. |
| 3 | Bods bijstellen | Goede CPI + weinig impressies → bod **+~20%**. Slechte CPI → omlaag/pauze. |
| 4 | Budget herverdelen | Van verliezers naar winnaars (binnen ~€20/dag). |
| 5 | Singular-check | Activeren installs ook (register/first_moment)? Stuur op **kwaliteit**, niet alleen prijs. |

**Verwachting per campagne:** Brand = laagste CPI (bijna gratis); Categorie = volume-motor, stuur hierop; Concurrent = lagere conversie, wees streng; Discovery = beoordeel op wélke termen het oplevert.

**Spelregels:** eerste 3–4 dagen niet sleutelen (Apple leert); max ~20% bod-wijziging per keer; niet alles tegelijk veranderen.

**Maandelijks (10 min):** budgetverdeling herzien, hardnekkige verliezers pauzeren, winnaars opschalen (+20% budget zolang CPI < doel).
