import React from "react";
import {
  AbsoluteFill,
  Sequence,
  useCurrentFrame,
  useVideoConfig,
  interpolate,
  spring,
  Img,
  staticFile,
} from "remotion";
import { Audio } from "@remotion/media";
import { QRCodeSVG } from "qrcode.react";

const COLORS = {
  bg: "#0c1821",
  bgCard: "#132530",
  primary: "#1b7a95",
  primaryLight: "#66c4e1",
  primaryDark: "#155d73",
  accent: "#d97706",
  white: "#f8fafc",
  muted: "#94a3b8",
  success: "#4ade80",
};

const FONT = "'Hanken Grotesk', 'Inter', system-ui, sans-serif";

export const NARRATION_SCRIPT: Record<string, string> = {
  intro:
    "Introducing pw_teaser, version 7. Powerful, dynamic page teasers for TYPO3 CMS. Built on Extbase and the Fluid template engine. Now fully compatible with TYPO3 thirteen and TYPO3 fourteen.",
  features:
    "Six page sources — from direct children to deep recursive trees, or hand-picked custom pages. Filter by categories with AND, OR, and NOT logic. Sort by title, date, manual order, or even random. Show or hide nav-hidden pages, specific doktypes, or individual UIDs. Load content elements per page for rich teasers. Every option configurable from the backend FlexForm.",
  presetMode:
    "Mode one: Preset. The recommended default. Integrators define named template configurations in TypoScript. Editors simply choose from a dropdown — no file paths, no confusion. Three presets ship out of the box: Default for full-featured teasers, Headline and Images for compact cards, and Headline Only for minimal lists. Register your own presets with just a few lines of TypoScript.",
  fileMode:
    "Mode two: File. Point to a single Fluid template for complete design freedom. Specify the template path, plus optional partial and layout directories. Perfect for one-off designs that don't fit into the preset system. Set it in the FlexForm or via TypoScript.",
  directoryMode:
    "Mode three: Directory. Follow the standard Extbase controller-action convention. Provide a template root directory and Fluid resolves Teaser slash Index dot html automatically. Full support for partials, layouts, and the standard override hierarchy. Best for complex projects with shared template components.",
  pagination:
    "Pagination built in. Simple Pagination ships as the default — previous and next, clean and reliable. For numbered page navigation with ellipsis, install Georg Ringer's numbered-pagination package via Composer. Configure it with a single TypoScript line. Add route enhancers for clean URLs like page-2, page-3.",
  extensible:
    "Extend the result set with PSR-14 events. The Modify Pages Event fires before rendering and gives you the complete page array. Filter pages, sort them, enrich them with external data — your listener, your logic. Register it as an event listener attribute and you're done.",
  outro:
    "pw_teaser version 7. Built for TYPO3 thirteen and fourteen. PHP 8.2 and above. Three template modes. Category filtering. Configurable pagination. PSR-14 extensibility. Open source. Production ready. Install it today.",
};

const DEFAULT_SCENE_DURATIONS: Record<string, number> = {
  intro: 300,
  features: 450,
  presetMode: 420,
  fileMode: 330,
  directoryMode: 360,
  pagination: 390,
  extensible: 360,
  outro: 360,
};

const SCENE_DURATIONS: Record<string, number> = (() => {
  try {
    return require("./narration-durations").SCENE_DURATIONS;
  } catch {
    return DEFAULT_SCENE_DURATIONS;
  }
})();

function SceneAudio({ sceneId }: { sceneId: string }) {
  const src = staticFile(`voiceover/${sceneId}.mp3`);
  return <Audio src={src} volume={1} />;
}

function FadeSlideIn({
  children,
  delay = 0,
  direction = "up",
}: {
  children: React.ReactNode;
  delay?: number;
  direction?: "up" | "left" | "right";
}) {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();

  const opacity = interpolate(frame - delay, [0, fps * 0.5], [0, 1], {
    extrapolateLeft: "clamp",
    extrapolateRight: "clamp",
  });

  const axis = direction === "up" ? "Y" : "X";
  const dist =
    direction === "right" ? 60 : direction === "left" ? -60 : 30;
  const move = interpolate(frame - delay, [0, fps * 0.5], [dist, 0], {
    extrapolateLeft: "clamp",
    extrapolateRight: "clamp",
  });

  return (
    <div
      style={{
        opacity,
        transform: `translate${axis}(${move}px)`,
      }}
    >
      {children}
    </div>
  );
}

function NarrationSubtitle({ text, sceneDurationFrames }: { text: string; sceneDurationFrames: number }) {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();

  const WORDS_PER_CHUNK = 5;
  const words = text.split(" ");
  const chunks: string[] = [];
  for (let i = 0; i < words.length; i += WORDS_PER_CHUNK) {
    chunks.push(words.slice(i, i + WORDS_PER_CHUNK).join(" "));
  }

  const startOffset = fps * 0.3;
  const endPadding = fps * 1.5;
  const availableFrames = sceneDurationFrames - startOffset - endPadding;
  const framesPerChunk = Math.max(fps * 1.2, availableFrames / chunks.length);
  const fadeDur = fps * 0.25;

  const activeIndex = Math.min(
    Math.max(0, Math.floor((frame - startOffset) / framesPerChunk)),
    chunks.length - 1,
  );
  const chunkStart = startOffset + activeIndex * framesPerChunk;
  const chunkEnd = chunkStart + framesPerChunk;

  const chunkIn = interpolate(frame, [chunkStart, chunkStart + fadeDur], [0, 1], {
    extrapolateLeft: "clamp", extrapolateRight: "clamp",
  });
  const chunkOut = interpolate(frame, [chunkEnd - fadeDur * 0.7, chunkEnd], [1, 0], {
    extrapolateLeft: "clamp", extrapolateRight: "clamp",
  });
  const isLastChunk = activeIndex === chunks.length - 1;
  const opacity = frame < startOffset ? 0 : isLastChunk ? chunkIn : Math.min(chunkIn, chunkOut);

  const slideY = interpolate(frame, [chunkStart, chunkStart + fadeDur], [10, 0], {
    extrapolateLeft: "clamp", extrapolateRight: "clamp",
  });

  return (
    <div style={{ position: "absolute", bottom: 70, left: 0, right: 0, display: "flex", justifyContent: "center", zIndex: 50 }}>
      <div
        style={{
          padding: "14px 44px",
          backgroundColor: "rgba(0,0,0,0.7)",
          borderRadius: 40,
          border: "1px solid rgba(255,255,255,0.08)",
          backdropFilter: "blur(12px)",
          opacity,
          transform: `translateY(${slideY}px)`,
        }}
      >
        <span
          style={{
            fontSize: 32,
            fontWeight: 400,
            color: COLORS.white,
            fontFamily: FONT,
            letterSpacing: "0.01em",
            whiteSpace: "nowrap" as const,
          }}
        >
          {chunks[activeIndex]}
        </span>
      </div>
    </div>
  );
}

function SceneIntro() {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();

  const logoScale = spring({ fps, frame, config: { damping: 80 } });
  const lineWidth = interpolate(frame, [fps * 0.5, fps * 1.5], [0, 400], {
    extrapolateLeft: "clamp",
    extrapolateRight: "clamp",
  });

  return (
    <AbsoluteFill
      style={{
        backgroundColor: COLORS.bg,
        justifyContent: "center",
        alignItems: "center",
        fontFamily: FONT,
      }}
    >
      <div style={{ textAlign: "center", transform: `scale(${logoScale})` }}>
        <div
          style={{
            fontSize: 96,
            fontWeight: 700,
            color: COLORS.primaryLight,
            letterSpacing: "-0.03em",
          }}
        >
          pw_teaser
        </div>
        <div
          style={{
            width: lineWidth,
            height: 3,
            background: `linear-gradient(90deg, transparent, ${COLORS.primary}, transparent)`,
            margin: "20px auto",
          }}
        />
        <FadeSlideIn delay={fps * 0.6}>
          <div
            style={{
              fontSize: 28,
              fontWeight: 400,
              color: COLORS.muted,
              letterSpacing: "0.08em",
              textTransform: "uppercase" as const,
            }}
          >
            Page Teasers for TYPO3 CMS
          </div>
        </FadeSlideIn>
        <FadeSlideIn delay={fps * 1.2}>
          <div
            style={{
              display: "flex",
              gap: 16,
              justifyContent: "center",
              marginTop: 40,
            }}
          >
            {["TYPO3 13", "TYPO3 14", "PHP 8.2+"].map((badge) => (
              <div
                key={badge}
                style={{
                  padding: "8px 24px",
                  borderRadius: 8,
                  border: `1px solid ${COLORS.primary}`,
                  color: COLORS.primaryLight,
                  fontSize: 18,
                  fontWeight: 500,
                }}
              >
                {badge}
              </div>
            ))}
          </div>
        </FadeSlideIn>
      </div>
      <SceneAudio sceneId="intro" />
      <NarrationSubtitle text={NARRATION_SCRIPT.intro} sceneDurationFrames={SCENE_DURATIONS.intro} />
      <SceneTransition sceneDurationFrames={SCENE_DURATIONS.intro} />
    </AbsoluteFill>
  );
}

function SceneFeatures() {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();

  const features = [
    { icon: "📄", title: "Flexible Sources", desc: "Children, recursive, custom pages" },
    { icon: "🏷️", title: "Category Filter", desc: "AND/OR/NOT category modes" },
    { icon: "🌲", title: "Nested Pages", desc: "Flat or nested page trees" },
    { icon: "🎨", title: "Template Presets", desc: "Preset, file, or directory mode" },
    { icon: "📑", title: "Pagination", desc: "Built-in with configurable class" },
    { icon: "⚙️", title: "FlexForm Config", desc: "Full backend editor control" },
  ];

  return (
    <AbsoluteFill
      style={{
        backgroundColor: COLORS.bg,
        padding: 80,
        fontFamily: FONT,
      }}
    >
      <div style={{ display: "flex", gap: 48, alignItems: "flex-start" }}>
        <div style={{ width: 480 }}>
          <FadeSlideIn>
            <div style={{ fontSize: 48, fontWeight: 700, color: COLORS.white, marginBottom: 32 }}>
              Features
            </div>
          </FadeSlideIn>

          <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: 12 }}>
            {features.map((f, i) => {
              const scale = spring({ fps, frame: frame - i * 8, config: { damping: 80 } });
              return (
                <div
                  key={f.title}
                  style={{
                    transform: `scale(${Math.min(scale, 1)})`,
                    opacity: scale,
                    backgroundColor: COLORS.bgCard,
                    borderRadius: 12,
                    padding: 18,
                    border: `1px solid ${COLORS.primaryDark}44`,
                  }}
                >
                  <div style={{ fontSize: 24, marginBottom: 6 }}>{f.icon}</div>
                  <div style={{ fontSize: 16, fontWeight: 600, color: COLORS.primaryLight, marginBottom: 4 }}>
                    {f.title}
                  </div>
                  <div style={{ fontSize: 13, color: COLORS.muted }}>{f.desc}</div>
                </div>
              );
            })}
          </div>
        </div>

        <FadeSlideIn delay={fps * 1.2} direction="left">
          <div style={{ flex: 1, borderRadius: 16, overflow: "hidden", border: `2px solid ${COLORS.primaryDark}66`, boxShadow: "0 12px 48px rgba(0,0,0,0.5)" }}>
            <Img src={staticFile("screenshots/teaser-frontend-output.png")} style={{ width: "100%", display: "block" }} />
          </div>
        </FadeSlideIn>
      </div>

      <SceneAudio sceneId="features" />
      <NarrationSubtitle text={NARRATION_SCRIPT.features} sceneDurationFrames={SCENE_DURATIONS.features} />
      <SceneTransition sceneDurationFrames={SCENE_DURATIONS.features} />
    </AbsoluteFill>
  );
}

function ScenePresetMode() {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();

  const presets = [
    { key: "default", label: "Default", desc: "Full teaser with title, abstract, media" },
    { key: "headlineAndImage", label: "Headline & Images", desc: "Compact cards with headline and page media" },
    { key: "headlineOnly", label: "Headline only", desc: "Minimal list showing only page titles" },
  ];

  return (
    <AbsoluteFill style={{ backgroundColor: COLORS.bg, padding: 80, fontFamily: FONT }}>
      <div style={{ display: "flex", gap: 48, alignItems: "flex-start" }}>
        <div style={{ width: 460 }}>
          <FadeSlideIn>
            <div style={{ display: "flex", alignItems: "center", gap: 16, marginBottom: 12 }}>
              <div style={{ padding: "6px 16px", borderRadius: 8, backgroundColor: COLORS.primary, color: COLORS.white, fontSize: 16, fontWeight: 600 }}>
                MODE 1
              </div>
            </div>
          </FadeSlideIn>
          <FadeSlideIn delay={4}>
            <div style={{ fontSize: 48, fontWeight: 700, color: COLORS.primaryLight, marginBottom: 20 }}>Preset</div>
          </FadeSlideIn>
          <FadeSlideIn delay={10}>
            <div style={{ fontSize: 19, color: COLORS.muted, lineHeight: 1.6, marginBottom: 32 }}>
              Integrators define named templates in TypoScript. Editors pick from a dropdown.
            </div>
          </FadeSlideIn>

          {presets.map((p, i) => {
            const scale = spring({ fps, frame: frame - fps * 0.8 - i * 10, config: { damping: 80 } });
            return (
              <div key={p.key} style={{ transform: `scale(${Math.min(scale, 1)})`, opacity: scale, display: "flex", gap: 12, alignItems: "center", marginBottom: 14 }}>
                <div style={{ width: 8, height: 8, borderRadius: 4, backgroundColor: COLORS.primaryLight, flexShrink: 0 }} />
                <div>
                  <span style={{ fontSize: 17, fontWeight: 600, color: COLORS.white }}>{p.label}</span>
                  <span style={{ fontSize: 15, color: COLORS.muted, marginLeft: 10 }}>— {p.desc}</span>
                </div>
              </div>
            );
          })}

          <div style={{ backgroundColor: COLORS.bgCard, borderRadius: 12, padding: 20, border: `1px solid ${COLORS.primaryDark}44`, marginTop: 24 }}>
            <div style={{ fontSize: 11, color: COLORS.muted, marginBottom: 10, textTransform: "uppercase" as const, letterSpacing: "0.1em" }}>TypoScript</div>
            <div style={{ fontFamily: "'SF Mono', 'Fira Code', monospace", fontSize: 12, lineHeight: 1.8 }}>
              <span style={{ color: COLORS.muted }}>{"plugin"}</span><span style={{ color: COLORS.white }}>{".tx_pwteaser {"}</span><br />
              <span style={{ color: COLORS.muted }}>{"  view"}</span><span style={{ color: COLORS.white }}>{".presets {"}</span><br />
              <span style={{ color: COLORS.accent }}>{"    myPreset"}</span><span style={{ color: COLORS.white }}>{" {"}</span><br />
              <span style={{ color: COLORS.primaryLight }}>{"      label"}</span><span style={{ color: COLORS.white }}>{" = "}</span><span style={{ color: COLORS.success }}>{"Custom Layout"}</span><br />
              <span style={{ color: COLORS.primaryLight }}>{"      templateRootFile"}</span><span style={{ color: COLORS.white }}>{" = "}</span><span style={{ color: COLORS.success }}>{"EXT:site/...html"}</span><br />
              <span style={{ color: COLORS.white }}>{"    }"}</span><br />
              <span style={{ color: COLORS.white }}>{"  }"}</span><br />
              <span style={{ color: COLORS.white }}>{"}"}</span>
            </div>
          </div>
        </div>

        <FadeSlideIn delay={fps * 0.5} direction="left">
          <div style={{ flex: 1, borderRadius: 12, overflow: "hidden", border: `2px solid ${COLORS.primaryDark}66`, boxShadow: "0 8px 32px rgba(0,0,0,0.4)" }}>
            <Img src={staticFile("screenshots/template-preset-mode.png")} style={{ width: "100%", display: "block" }} />
          </div>
        </FadeSlideIn>
      </div>

      <SceneAudio sceneId="presetMode" />
      <NarrationSubtitle text={NARRATION_SCRIPT.presetMode} sceneDurationFrames={SCENE_DURATIONS.presetMode} />
      <SceneTransition sceneDurationFrames={SCENE_DURATIONS.presetMode} />
    </AbsoluteFill>
  );
}

function SceneFileMode() {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();

  const fields = [
    { label: "Template Root File", value: "EXT:my_sitepackage/.../CustomTeaser.html", required: true },
    { label: "Partial Root Path", value: "EXT:my_sitepackage/.../Partials/", required: false },
    { label: "Layout Root Path", value: "EXT:my_sitepackage/.../Layouts/", required: false },
  ];

  return (
    <AbsoluteFill style={{ backgroundColor: COLORS.bg, padding: 80, fontFamily: FONT }}>
      <FadeSlideIn>
        <div style={{ display: "flex", alignItems: "center", gap: 16, marginBottom: 12 }}>
          <div style={{ padding: "6px 16px", borderRadius: 8, backgroundColor: COLORS.accent, color: COLORS.white, fontSize: 16, fontWeight: 600 }}>
            MODE 2
          </div>
        </div>
      </FadeSlideIn>
      <FadeSlideIn delay={4}>
        <div style={{ fontSize: 52, fontWeight: 700, color: COLORS.accent, marginBottom: 16 }}>File</div>
      </FadeSlideIn>
      <FadeSlideIn delay={10}>
        <div style={{ fontSize: 22, color: COLORS.muted, lineHeight: 1.6, marginBottom: 50, maxWidth: 800 }}>
          Point to a single Fluid template file. Full design freedom for one-off layouts.
        </div>
      </FadeSlideIn>

      <div style={{ display: "flex", gap: 48 }}>
        <div style={{ width: 460 }}>
          {fields.map((f, i) => {
            const slideY = interpolate(frame - fps * 0.6 - i * 8, [0, fps * 0.4], [40, 0], { extrapolateLeft: "clamp", extrapolateRight: "clamp" });
            const opacity = interpolate(frame - fps * 0.6 - i * 8, [0, fps * 0.3], [0, 1], { extrapolateLeft: "clamp", extrapolateRight: "clamp" });
            return (
              <div key={f.label} style={{ opacity, transform: `translateY(${slideY}px)`, marginBottom: 20 }}>
                <div style={{ fontSize: 13, color: f.required ? COLORS.accent : COLORS.muted, marginBottom: 6, fontWeight: 600 }}>
                  {f.label} {f.required && "(required)"}
                </div>
                <div style={{ backgroundColor: COLORS.bgCard, padding: "12px 16px", borderRadius: 10, border: `1px solid ${COLORS.primaryDark}44`, fontFamily: "'SF Mono', monospace", fontSize: 13, color: COLORS.primaryLight }}>
                  {f.value}
                </div>
              </div>
            );
          })}
          <div style={{ backgroundColor: COLORS.bgCard, borderRadius: 12, padding: 20, border: `1px solid ${COLORS.accent}44`, borderTop: `3px solid ${COLORS.accent}`, marginTop: 12 }}>
            <div style={{ fontSize: 15, fontWeight: 600, color: COLORS.accent, marginBottom: 8 }}>When to use</div>
            <div style={{ fontSize: 14, color: COLORS.muted, lineHeight: 1.7 }}>
              A single, self-contained template. Ideal for project-specific designs.
            </div>
          </div>
        </div>

        <FadeSlideIn delay={fps * 1.2} direction="left">
          <div style={{ flex: 1, borderRadius: 12, overflow: "hidden", border: `2px solid ${COLORS.accent}66`, boxShadow: "0 8px 32px rgba(0,0,0,0.4)" }}>
            <Img src={staticFile("screenshots/template-file-mode.png")} style={{ width: "100%", display: "block" }} />
          </div>
        </FadeSlideIn>
      </div>

      <SceneAudio sceneId="fileMode" />
      <NarrationSubtitle text={NARRATION_SCRIPT.fileMode} sceneDurationFrames={SCENE_DURATIONS.fileMode} />
      <SceneTransition sceneDurationFrames={SCENE_DURATIONS.fileMode} />
    </AbsoluteFill>
  );
}

function SceneDirectoryMode() {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();

  const tree = [
    { indent: 0, name: "Templates/PwTeaser/", type: "dir" },
    { indent: 1, name: "Teaser/", type: "dir" },
    { indent: 2, name: "Index.html", type: "file", highlight: true },
    { indent: 1, name: "Partials/", type: "dir" },
    { indent: 2, name: "PageCard.html", type: "file", highlight: false },
    { indent: 1, name: "Layouts/", type: "dir" },
    { indent: 2, name: "Default.html", type: "file", highlight: false },
  ];

  return (
    <AbsoluteFill style={{ backgroundColor: COLORS.bg, padding: 80, fontFamily: FONT }}>
      <FadeSlideIn>
        <div style={{ display: "flex", alignItems: "center", gap: 16, marginBottom: 12 }}>
          <div style={{ padding: "6px 16px", borderRadius: 8, backgroundColor: COLORS.success, color: "#0c1821", fontSize: 16, fontWeight: 600 }}>
            MODE 3
          </div>
        </div>
      </FadeSlideIn>
      <FadeSlideIn delay={4}>
        <div style={{ fontSize: 52, fontWeight: 700, color: COLORS.success, marginBottom: 16 }}>Directory</div>
      </FadeSlideIn>
      <FadeSlideIn delay={10}>
        <div style={{ fontSize: 22, color: COLORS.muted, lineHeight: 1.6, marginBottom: 50, maxWidth: 800 }}>
          Standard Extbase convention. Fluid resolves templates automatically.
        </div>
      </FadeSlideIn>

      <div style={{ display: "flex", gap: 48 }}>
        <div style={{ width: 460 }}>
          <div style={{ backgroundColor: COLORS.bgCard, borderRadius: 16, padding: 28, border: `1px solid ${COLORS.primaryDark}44`, marginBottom: 16 }}>
            {tree.map((item, i) => {
              const lineOpacity = interpolate(frame - fps * 0.6 - i * 5, [0, fps * 0.3], [0, 1], { extrapolateLeft: "clamp", extrapolateRight: "clamp" });
              return (
                <div key={i} style={{ opacity: lineOpacity, display: "flex", alignItems: "center", gap: 8, paddingLeft: item.indent * 24, marginBottom: 8 }}>
                  <span style={{ fontSize: 15, color: item.type === "dir" ? COLORS.accent : (item.highlight ? COLORS.success : COLORS.primaryLight) }}>
                    {item.type === "dir" ? "📁" : "📄"}
                  </span>
                  <span style={{ fontFamily: "'SF Mono', monospace", fontSize: 16, color: item.highlight ? COLORS.success : COLORS.white, fontWeight: item.highlight ? 700 : 400 }}>
                    {item.name}
                  </span>
                  {item.highlight && (
                    <span style={{ fontSize: 12, color: COLORS.success, marginLeft: 6, padding: "2px 8px", borderRadius: 6, border: `1px solid ${COLORS.success}44`, backgroundColor: `${COLORS.success}11` }}>
                      auto-resolved
                    </span>
                  )}
                </div>
              );
            })}
          </div>
          <div style={{ backgroundColor: COLORS.bgCard, borderRadius: 12, padding: 20, border: `1px solid ${COLORS.success}44`, borderTop: `3px solid ${COLORS.success}` }}>
            <div style={{ fontSize: 15, fontWeight: 600, color: COLORS.success, marginBottom: 8 }}>Best for</div>
            <div style={{ fontSize: 14, color: COLORS.muted, lineHeight: 1.7 }}>
              Complex projects with shared partials and the standard Extbase override hierarchy.
            </div>
          </div>
        </div>

        <FadeSlideIn delay={fps * 1.5} direction="left">
          <div style={{ flex: 1, borderRadius: 12, overflow: "hidden", border: `2px solid ${COLORS.success}66`, boxShadow: "0 8px 32px rgba(0,0,0,0.4)" }}>
            <Img src={staticFile("screenshots/template-directory-mode.png")} style={{ width: "100%", display: "block" }} />
          </div>
        </FadeSlideIn>
      </div>

      <SceneAudio sceneId="directoryMode" />
      <NarrationSubtitle text={NARRATION_SCRIPT.directoryMode} sceneDurationFrames={SCENE_DURATIONS.directoryMode} />
      <SceneTransition sceneDurationFrames={SCENE_DURATIONS.directoryMode} />
    </AbsoluteFill>
  );
}

function ScenePagination() {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();

  const pages = [1, 2, 3, "...", 12, 13, 14];

  return (
    <AbsoluteFill
      style={{
        backgroundColor: COLORS.bg,
        padding: 80,
        fontFamily: FONT,
        justifyContent: "center",
        alignItems: "center",
      }}
    >
      <FadeSlideIn>
        <div
          style={{
            fontSize: 48,
            fontWeight: 700,
            color: COLORS.white,
            marginBottom: 20,
            textAlign: "center",
          }}
        >
          Pagination
        </div>
      </FadeSlideIn>

      <FadeSlideIn delay={fps * 0.4}>
        <div
          style={{
            fontSize: 20,
            color: COLORS.muted,
            marginBottom: 60,
            textAlign: "center",
          }}
        >
          SimplePagination (default) or NumberedPagination (optional)
        </div>
      </FadeSlideIn>

      <div style={{ display: "flex", gap: 8, justifyContent: "center" }}>
        {pages.map((p, i) => {
          const scale = spring({
            fps,
            frame: frame - fps * 0.8 - i * 4,
            config: { damping: 80 },
          });
          const isActive = p === 3;
          return (
            <div
              key={i}
              style={{
                transform: `scale(${Math.min(scale, 1)})`,
                opacity: scale,
                width: typeof p === "number" ? 56 : 40,
                height: 56,
                borderRadius: 12,
                display: "flex",
                alignItems: "center",
                justifyContent: "center",
                fontSize: 20,
                fontWeight: isActive ? 700 : 400,
                backgroundColor: isActive ? COLORS.primary : COLORS.bgCard,
                color: isActive ? COLORS.white : COLORS.muted,
                border: `1px solid ${isActive ? COLORS.primary : COLORS.primaryDark}44`,
              }}
            >
              {p}
            </div>
          );
        })}
      </div>

      <FadeSlideIn delay={fps * 1.5}>
        <div
          style={{
            marginTop: 50,
            padding: "16px 32px",
            backgroundColor: COLORS.bgCard,
            borderRadius: 12,
            border: `1px solid ${COLORS.primaryDark}44`,
            fontFamily: "monospace",
            fontSize: 16,
            color: COLORS.primaryLight,
            textAlign: "center",
          }}
        >
          plugin.tx_pwteaser.settings.paginationClass =
          GeorgRinger\NumberedPagination\NumberedPagination
        </div>
      </FadeSlideIn>

      <SceneAudio sceneId="pagination" />
      <NarrationSubtitle text={NARRATION_SCRIPT.pagination} sceneDurationFrames={SCENE_DURATIONS.pagination} />
      <SceneTransition sceneDurationFrames={SCENE_DURATIONS.pagination} />
    </AbsoluteFill>
  );
}

function SceneExtensible() {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();

  const SYN = {
    keyword: "#c792ea",
    type: "#ffcb6b",
    string: "#c3e88d",
    variable: "#f07178",
    method: "#82aaff",
    comment: "#546e7a",
    punctuation: COLORS.white,
    namespace: "#89ddff",
  };

  const codeTokens: { text: string; color: string }[][] = [
    [
      { text: "use ", color: SYN.keyword },
      { text: "PwTeaserTeam\\PwTeaser\\Event\\", color: SYN.namespace },
      { text: "ModifyPagesEvent", color: SYN.type },
      { text: ";", color: SYN.punctuation },
    ],
    [],
    [
      { text: "#[", color: SYN.namespace },
      { text: "AsEventListener", color: SYN.type },
      { text: "]", color: SYN.namespace },
    ],
    [
      { text: "class ", color: SYN.keyword },
      { text: "YourListener", color: SYN.type },
    ],
    [{ text: "{", color: SYN.punctuation }],
    [
      { text: "    ", color: SYN.punctuation },
      { text: "public function ", color: SYN.keyword },
      { text: "__invoke", color: SYN.method },
      { text: "(", color: SYN.punctuation },
    ],
    [
      { text: "        ", color: SYN.punctuation },
      { text: "ModifyPagesEvent ", color: SYN.type },
      { text: "$event", color: SYN.variable },
    ],
    [
      { text: "    ", color: SYN.punctuation },
      { text: "): ", color: SYN.punctuation },
      { text: "void ", color: SYN.keyword },
      { text: "{", color: SYN.punctuation },
    ],
    [
      { text: "        ", color: SYN.punctuation },
      { text: "$pages", color: SYN.variable },
      { text: " = ", color: SYN.punctuation },
      { text: "$event", color: SYN.variable },
      { text: "->", color: SYN.punctuation },
      { text: "getPages", color: SYN.method },
      { text: "();", color: SYN.punctuation },
    ],
    [{ text: "        // filter, sort, enrich...", color: SYN.comment }],
    [
      { text: "        ", color: SYN.punctuation },
      { text: "$event", color: SYN.variable },
      { text: "->", color: SYN.punctuation },
      { text: "setPages", color: SYN.method },
      { text: "(", color: SYN.punctuation },
      { text: "$pages", color: SYN.variable },
      { text: ");", color: SYN.punctuation },
    ],
    [{ text: "    }", color: SYN.punctuation }],
    [{ text: "}", color: SYN.punctuation }],
  ];

  return (
    <AbsoluteFill
      style={{
        backgroundColor: COLORS.bg,
        padding: 80,
        fontFamily: FONT,
      }}
    >
      <FadeSlideIn>
        <div
          style={{
            fontSize: 48,
            fontWeight: 700,
            color: COLORS.white,
            marginBottom: 40,
          }}
        >
          PSR-14 Events
        </div>
      </FadeSlideIn>

      <div
        style={{
          backgroundColor: "#0d1b24",
          borderRadius: 16,
          padding: 40,
          border: `1px solid ${COLORS.primaryDark}44`,
          maxWidth: 950,
        }}
      >
        {codeTokens.map((tokens, i) => {
          const lineOpacity = interpolate(
            frame - fps * 0.3 - i * 3,
            [0, fps * 0.3],
            [0, 1],
            { extrapolateLeft: "clamp", extrapolateRight: "clamp" }
          );
          return (
            <div
              key={i}
              style={{
                opacity: lineOpacity,
                fontFamily: "'SF Mono', 'Fira Code', monospace",
                fontSize: 19,
                lineHeight: 1.8,
              }}
            >
              {tokens.length === 0
                ? "\u00A0"
                : tokens.map((t, j) => (
                    <span key={j} style={{ color: t.color }}>{t.text}</span>
                  ))}
            </div>
          );
        })}
      </div>

      <SceneAudio sceneId="extensible" />
      <NarrationSubtitle text={NARRATION_SCRIPT.extensible} sceneDurationFrames={SCENE_DURATIONS.extensible} />
      <SceneTransition sceneDurationFrames={SCENE_DURATIONS.extensible} />
    </AbsoluteFill>
  );
}

function SceneOutro() {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();

  const scale = spring({ fps, frame, config: { damping: 60 } });
  const glowOpacity = interpolate(
    frame,
    [fps * 2, fps * 3],
    [0, 0.3],
    { extrapolateLeft: "clamp", extrapolateRight: "clamp" }
  );

  return (
    <AbsoluteFill
      style={{
        backgroundColor: COLORS.bg,
        justifyContent: "center",
        alignItems: "center",
        fontFamily: FONT,
      }}
    >
      <div
        style={{
          position: "absolute",
          width: 600,
          height: 600,
          borderRadius: "50%",
          background: `radial-gradient(circle, ${COLORS.primary}${Math.round(glowOpacity * 255).toString(16).padStart(2, "0")}, transparent 70%)`,
        }}
      />
      <div style={{ textAlign: "center", transform: `scale(${scale})` }}>
        <div
          style={{
            fontSize: 80,
            fontWeight: 700,
            color: COLORS.primaryLight,
            letterSpacing: "-0.03em",
          }}
        >
          pw_teaser
        </div>
        <FadeSlideIn delay={fps * 0.5}>
          <div
            style={{
              fontSize: 36,
              fontWeight: 600,
              color: COLORS.white,
              marginTop: 16,
            }}
          >
            Version 7.0
          </div>
        </FadeSlideIn>
        <FadeSlideIn delay={fps * 0.8}>
          <div
            style={{
              display: "flex",
              gap: 24,
              justifyContent: "center",
              marginTop: 40,
            }}
          >
            {["Open Source", "TYPO3 13 & 14", "PHP 8.2+"].map((t) => (
              <div
                key={t}
                style={{
                  fontSize: 20,
                  color: COLORS.muted,
                  padding: "8px 20px",
                  border: `1px solid ${COLORS.primaryDark}66`,
                  borderRadius: 8,
                }}
              >
                {t}
              </div>
            ))}
          </div>
        </FadeSlideIn>
        <FadeSlideIn delay={fps * 1.5}>
          <div
            style={{
              marginTop: 50,
              fontFamily: "'SF Mono', 'Fira Code', monospace",
              fontSize: 22,
              color: COLORS.primary,
              letterSpacing: "0.02em",
            }}
          >
            composer require t3/pw_teaser
          </div>
        </FadeSlideIn>
        <FadeSlideIn delay={fps * 2.5}>
          <div style={{ marginTop: 40, textAlign: "center" }}>
            <div style={{ fontSize: 14, color: COLORS.muted, letterSpacing: "0.03em" }}>
              Originally by <span style={{ color: COLORS.white, fontWeight: 500 }}>Armin Vieweg</span>
              {" · "}Updated for TYPO3 13 & 14 by <span style={{ color: COLORS.primaryLight, fontWeight: 500 }}>webconsulting</span>
            </div>
          </div>
        </FadeSlideIn>
      </div>
      <SceneAudio sceneId="outro" />
      <NarrationSubtitle text={NARRATION_SCRIPT.outro} sceneDurationFrames={SCENE_DURATIONS.outro} />
      <SceneTransition sceneDurationFrames={SCENE_DURATIONS.outro} />
    </AbsoluteFill>
  );
}

const LEAD_IN_FRAMES = Math.round(30 * 1.5);
const END_CARD_FRAMES = Math.round(30 * 17);

const GITHUB_ICON = `data:image/svg+xml,${encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#f8fafc"><path d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.02 12.02 0 0024 12c0-6.63-5.37-12-12-12z"/></svg>')}`;
const WEB_ICON = `data:image/svg+xml,${encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" viewBox="4 0 48 32"><path fill="#66c4e1" d="M49.82,3.89c.6-.26,1.08-.06,1.07.47l-.2,6.88c-.02.52-.52,1.16-1.12,1.43l-18.32,8.02c-.61.27-1.08.06-1.07-.47l.21-6.88c.02-.52.52-1.16,1.12-1.43L49.82,3.89Z"/><path fill="#1b7a95" d="M22.39,20.12c.28.6.08,1.08-.44,1.08l-6.89-.04c-.52,0-1.17-.49-1.45-1.09L5.15,1.95c-.28-.6-.08-1.08.44-1.08l6.89.04c.52,0,1.17.49,1.45,1.09l8.46,18.12Z"/><path fill="#66c4e1" d="M49.5,14.66c.6-.26,1.08-.06,1.07.47l-.2,6.88c-.02.52-.52,1.16-1.12,1.43l-18.32,8.02c-.61.27-1.08.06-1.07-.47l.21-6.88c.02-.52.52-1.16,1.12-1.43l18.32-8.02Z"/><path fill="#1b7a95" d="M28.7,19.9l.04-6.89c0-.71.32-1.44.84-2.02L25.38,2c-.28-.6-.93-1.08-1.45-1.09l-6.89-.04c-.52,0-.72.48-.44,1.08l8.46,18.12c.28.6.93,1.09,1.45,1.09h2.56c-.24-.35-.38-.79-.37-1.25Z"/><path fill="#1b7a95" d="M33.52,21.18c.11-.02.21-.06.28-.13l-.28.13Z"/><path fill="#1b7a95" d="M37.74,6.83l1.11-.52-2.02-4.32c-.28-.6-.93-1.09-1.45-1.09l-6.89-.04c-.52,0-.72.48-.44,1.08l3.61,7.73,6.08-2.84Z"/></svg>')}`;
const YOUTUBE_ICON = `data:image/svg+xml,${encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#FF0000"><path d="M23.498 6.186a3.016 3.016 0 00-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 00.502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 002.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 002.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>')}`;
const X_ICON = `data:image/svg+xml,${encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#f8fafc"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>')}`;

function SceneTransition({ sceneDurationFrames }: { sceneDurationFrames: number }) {
  const frame = useCurrentFrame();
  const fadeStart = sceneDurationFrames - 15;
  const blackOpacity = interpolate(frame, [fadeStart, sceneDurationFrames], [0, 1], {
    extrapolateLeft: "clamp",
    extrapolateRight: "clamp",
  });
  return (
    <AbsoluteFill style={{ backgroundColor: COLORS.bg, opacity: blackOpacity, zIndex: 100 }} />
  );
}

function EndCardScene() {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();
  const dur = END_CARD_FRAMES;

  const socials = [
    { icon: WEB_ICON, handle: "webconsulting.at", qrUrl: "https://webconsulting.at", highlight: true, badge: "Tutorials & deep dives", badgeSub: "/blog" },
    { icon: GITHUB_ICON, handle: "@dirnbauer", qrUrl: "https://github.com/dirnbauer" },
    { icon: YOUTUBE_ICON, handle: "@webconsulting-curt", qrUrl: "https://youtube.com/@webconsulting-curt" },
    { icon: X_ICON, handle: "@KDirnbauer", qrUrl: "https://x.com/KDirnbauer" },
  ];

  const silentHold = fps * 7;
  const animEnd = dur - silentHold;
  const finaleStart = animEnd - fps * 3.5;

  const headingY = interpolate(frame, [finaleStart, animEnd], [0, -200], {
    extrapolateLeft: "clamp", extrapolateRight: "clamp",
  });
  const headingOpacity = interpolate(frame, [finaleStart, animEnd - fps * 1], [1, 0], {
    extrapolateLeft: "clamp", extrapolateRight: "clamp",
  });
  const footerY = interpolate(frame, [finaleStart, animEnd], [0, 180], {
    extrapolateLeft: "clamp", extrapolateRight: "clamp",
  });
  const footerOpacity = interpolate(frame, [finaleStart, animEnd - fps * 1], [1, 0], {
    extrapolateLeft: "clamp", extrapolateRight: "clamp",
  });
  const qrScale = interpolate(frame, [finaleStart, animEnd], [1.0, 1.4], {
    extrapolateLeft: "clamp", extrapolateRight: "clamp",
  });
  const contentFadeIn = interpolate(frame, [0, fps * 1.5], [0, 1], {
    extrapolateLeft: "clamp", extrapolateRight: "clamp",
  });

  return (
    <AbsoluteFill style={{ backgroundColor: COLORS.bg, justifyContent: "center", alignItems: "center", fontFamily: FONT }}>
      <div style={{ display: "flex", flexDirection: "column", alignItems: "center", gap: 36 }}>
        <div
          style={{
            opacity: Math.min(contentFadeIn, headingOpacity),
            fontSize: 24,
            color: COLORS.muted,
            fontWeight: 300,
            letterSpacing: "0.15em",
            textTransform: "uppercase" as const,
            transform: `translateY(${headingY}px)`,
          }}
        >
          Find us on
        </div>
        <div style={{ display: "flex", gap: 44, alignItems: "flex-start", transform: `scale(${qrScale})`, transformOrigin: "center center" }}>
          {socials.map((social, i) => {
            const stagger = dur * 0.04 + i * dur * 0.03;
            const animDur = dur * 0.09;
            const itemOpacity = interpolate(frame, [stagger, stagger + animDur], [0, 1], { extrapolateLeft: "clamp", extrapolateRight: "clamp" });
            const slideUp = interpolate(frame, [stagger, stagger + animDur], [24, 0], { extrapolateLeft: "clamp", extrapolateRight: "clamp" });
            return (
              <div key={social.handle} style={{ display: "flex", flexDirection: "column", alignItems: "center", gap: 12, opacity: itemOpacity, transform: `translateY(${slideUp}px)` }}>
                <div style={{ display: "flex", alignItems: "center", gap: 10 }}>
                  <Img src={social.icon} style={{ width: 26, height: 26 }} />
                  <div style={{ fontSize: social.highlight ? 20 : 17, fontWeight: social.highlight ? 700 : 600, color: social.highlight ? COLORS.primaryLight : COLORS.white }}>{social.handle}</div>
                </div>
                <div style={{ backgroundColor: "#ffffff", padding: 12, borderRadius: 12, border: social.highlight ? `3px solid ${COLORS.primaryLight}` : "2px solid transparent" }}>
                  <QRCodeSVG value={social.qrUrl} size={120} bgColor="#ffffff" fgColor={COLORS.bg} level="M" />
                </div>
                {social.badge && (
                  <div style={{ display: "flex", flexDirection: "column", alignItems: "center", gap: 4 }}>
                    <div style={{ backgroundColor: `${COLORS.primaryLight}22`, border: `1px solid ${COLORS.primaryLight}`, borderRadius: 6, padding: "4px 12px" }}>
                      <span style={{ fontSize: 13, fontWeight: 700, color: COLORS.primaryLight }}>{social.badgeSub}</span>
                    </div>
                    <span style={{ fontSize: 11, color: COLORS.muted, fontWeight: 300 }}>{social.badge}</span>
                  </div>
                )}
                {!social.badge && (
                  <div style={{ fontSize: 12, color: COLORS.muted, fontWeight: 300 }}>{social.qrUrl.replace("https://", "")}</div>
                )}
              </div>
            );
          })}
        </div>
        <div style={{ opacity: Math.min(contentFadeIn, footerOpacity), display: "flex", flexDirection: "column", alignItems: "center", gap: 8, marginTop: 8, transform: `translateY(${footerY}px)` }}>
          <div style={{ display: "flex", alignItems: "center", gap: 12 }}>
            <Img src={staticFile("pw-teaser-icon.png")} style={{ width: 32, height: 32 }} />
            <div style={{ fontSize: 18, fontWeight: 600, color: COLORS.white }}>pw_teaser</div>
          </div>
          <div style={{ fontSize: 13, color: COLORS.muted, fontWeight: 300 }}>
            by Armin Vieweg · Updated by webconsulting
          </div>
        </div>
      </div>
    </AbsoluteFill>
  );
}

function BackgroundMusic() {
  const frame = useCurrentFrame();
  const { fps, durationInFrames } = useVideoConfig();

  let hasMusic = false;
  try {
    hasMusic = require("./music-config").HAS_BACKGROUND_MUSIC;
  } catch {
    hasMusic = false;
  }

  if (!hasMusic) return null;

  const sceneKeys = Object.keys(SCENE_DURATIONS);
  const sceneStarts: number[] = [];
  const sceneDurs: number[] = [];
  let cumulative = LEAD_IN_FRAMES;
  for (const key of sceneKeys) {
    sceneStarts.push(cumulative);
    sceneDurs.push(SCENE_DURATIONS[key]);
    cumulative += SCENE_DURATIONS[key];
  }

  const MUSIC_FULL = 0.15;
  const MUSIC_DUCKED = 0.06;
  const DUCK_RAMP = fps * 0.5;

  let vol = MUSIC_FULL;
  for (let i = 0; i < sceneStarts.length; i++) {
    const narrationStart = sceneStarts[i] + fps * 0.5;
    const narrationEnd = sceneStarts[i] + sceneDurs[i] - fps * 1.5;
    if (frame >= narrationStart && frame <= narrationEnd) {
      vol = MUSIC_DUCKED;
      break;
    }
    if (frame >= narrationStart - DUCK_RAMP && frame < narrationStart) {
      vol = interpolate(frame, [narrationStart - DUCK_RAMP, narrationStart], [MUSIC_FULL, MUSIC_DUCKED], { extrapolateLeft: "clamp", extrapolateRight: "clamp" });
      break;
    }
    if (frame >= narrationEnd && frame < narrationEnd + DUCK_RAMP) {
      vol = interpolate(frame, [narrationEnd, narrationEnd + DUCK_RAMP], [MUSIC_DUCKED, MUSIC_FULL], { extrapolateLeft: "clamp", extrapolateRight: "clamp" });
      break;
    }
  }

  const linearT = interpolate(frame, [0, LEAD_IN_FRAMES], [0, 1], { extrapolateLeft: "clamp", extrapolateRight: "clamp" });
  const fadeIn = Math.pow(linearT, 2.5);

  const silentFrom = durationInFrames - fps * 7;
  const fadeOut = interpolate(frame, [silentFrom - fps * 3, silentFrom], [1, 0], { extrapolateLeft: "clamp", extrapolateRight: "clamp" });

  const finalVolume = vol * fadeIn * fadeOut;

  try {
    return <Audio src={staticFile("music/background.mp3")} volume={finalVolume} loop />;
  } catch {
    return null;
  }
}

export { SCENE_DURATIONS, LEAD_IN_FRAMES, END_CARD_FRAMES };

export const ProductTour: React.FC = () => {
  let offset = LEAD_IN_FRAMES;
  const scenes: { component: React.FC; duration: number; id: string }[] = [
    { component: SceneIntro, duration: SCENE_DURATIONS.intro, id: "intro" },
    { component: SceneFeatures, duration: SCENE_DURATIONS.features, id: "features" },
    { component: ScenePresetMode, duration: SCENE_DURATIONS.presetMode, id: "presetMode" },
    { component: SceneFileMode, duration: SCENE_DURATIONS.fileMode, id: "fileMode" },
    { component: SceneDirectoryMode, duration: SCENE_DURATIONS.directoryMode, id: "directoryMode" },
    { component: ScenePagination, duration: SCENE_DURATIONS.pagination, id: "pagination" },
    { component: SceneExtensible, duration: SCENE_DURATIONS.extensible, id: "extensible" },
    { component: SceneOutro, duration: SCENE_DURATIONS.outro, id: "outro" },
  ];

  return (
    <AbsoluteFill style={{ backgroundColor: COLORS.bg }}>
      <BackgroundMusic />
      {scenes.map((scene) => {
        const from = offset;
        offset += scene.duration;
        const SceneComp = scene.component;
        return (
          <Sequence key={scene.id} from={from} durationInFrames={scene.duration}>
            <SceneComp />
          </Sequence>
        );
      })}
      <Sequence from={offset} durationInFrames={END_CARD_FRAMES}>
        <EndCardScene />
      </Sequence>
    </AbsoluteFill>
  );
};
