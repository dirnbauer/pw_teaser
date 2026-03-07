const { writeFileSync, mkdirSync, existsSync, rmSync, readdirSync } = require("fs");
const { execSync } = require("child_process");

const VOICE_ID = "onwK4e9ZLuTAKqWW03F9"; // Daniel - British, calm
const MODEL_ID = "eleven_multilingual_v2";
const OUTPUT_DIR = "public/voiceover";
const FPS = 30;
const PADDING_SECONDS = 2;

const NARRATION_SCRIPT: Record<string, string> = {
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

function applyPronunciationFixes(text: string): string {
  return text.replace(/TYPO3/g, "TYPO three");
}

async function generateWithElevenLabs(
  sceneId: string,
  text: string
): Promise<void> {
  const apiKey = process.env.ELEVENLABS_API_KEY;
  if (!apiKey) {
    throw new Error("ELEVENLABS_API_KEY not set. Export it or pass it inline.");
  }

  const spokenText = applyPronunciationFixes(text);
  console.log(`  Generating: ${sceneId} (${text.length} chars)`);

  const response = await fetch(
    `https://api.elevenlabs.io/v1/text-to-speech/${VOICE_ID}`,
    {
      method: "POST",
      headers: {
        "xi-api-key": apiKey,
        "Content-Type": "application/json",
        Accept: "audio/mpeg",
      },
      body: JSON.stringify({
        text: spokenText,
        model_id: MODEL_ID,
        voice_settings: {
          stability: 0.75,
          similarity_boost: 0.7,
          style: 0.35,
          use_speaker_boost: true,
        },
      }),
    }
  );

  if (!response.ok) {
    const err = await response.text();
    throw new Error(`ElevenLabs API error for "${sceneId}": ${err}`);
  }

  const audioBuffer = Buffer.from(await response.arrayBuffer());
  writeFileSync(`${OUTPUT_DIR}/${sceneId}.mp3`, audioBuffer);
  console.log(`  Wrote: ${OUTPUT_DIR}/${sceneId}.mp3`);
}

function generateWithMacOS(sceneId: string, text: string): void {
  const spokenText = applyPronunciationFixes(text);
  console.log(`  Generating (macOS say): ${sceneId}`);
  const aiffPath = `${OUTPUT_DIR}/${sceneId}.aiff`;
  const mp3Path = `${OUTPUT_DIR}/${sceneId}.mp3`;

  execSync(
    `say -v Daniel -o "${aiffPath}" "${spokenText.replace(/"/g, '\\"')}"`,
    { stdio: "inherit" }
  );
  execSync(`afconvert -f mp4f -d aac "${aiffPath}" "${mp3Path}"`, {
    stdio: "inherit",
  });
  rmSync(aiffPath, { force: true });
  console.log(`  Wrote: ${mp3Path}`);
}

function getAudioDuration(filePath: string): number {
  try {
    const output = execSync(`afinfo "${filePath}"`, {
      encoding: "utf-8",
      stdio: ["pipe", "pipe", "pipe"],
    });
    const match = output.match(/estimated duration:\s*([\d.]+)/);
    if (match) return parseFloat(match[1]);
    console.warn(`  afinfo output did not contain duration for ${filePath}`);
  } catch (e: any) {
    console.warn(`  afinfo failed for ${filePath}: ${e.message}`);
  }
  console.warn(`  Falling back to 5s for ${filePath}`);
  return 5;
}

async function main() {
  console.log("pw_teaser narration generator\n");

  if (!existsSync(OUTPUT_DIR)) {
    mkdirSync(OUTPUT_DIR, { recursive: true });
  }

  for (const f of readdirSync(OUTPUT_DIR)) {
    if (f.endsWith(".mp3") || f.endsWith(".wav") || f.endsWith(".aiff")) {
      rmSync(`${OUTPUT_DIR}/${f}`, { force: true });
    }
  }

  const useElevenLabs = !!process.env.ELEVENLABS_API_KEY;

  console.log(
    `Backend: ${useElevenLabs ? "ElevenLabs (Daniel voice)" : "macOS say (fallback)"}\n`
  );

  const scenes = Object.entries(NARRATION_SCRIPT);

  for (const [sceneId, text] of scenes) {
    if (useElevenLabs) {
      await generateWithElevenLabs(sceneId, text);
    } else {
      generateWithMacOS(sceneId, text);
    }
  }

  console.log("\nProbing audio durations...");
  const durations: Record<string, number> = {};

  for (const [sceneId] of scenes) {
    const filePath = `${OUTPUT_DIR}/${sceneId}.mp3`;
    const durationSec = getAudioDuration(filePath);
    const frames = Math.ceil((durationSec + PADDING_SECONDS) * FPS);
    durations[sceneId] = frames;
    console.log(
      `  ${sceneId}: ${durationSec.toFixed(1)}s + ${PADDING_SECONDS}s padding = ${frames} frames`
    );
  }

  const totalFrames = Object.values(durations).reduce((a, b) => a + b, 0);

  const durationsCode = `export const SCENE_DURATIONS: Record<string, number> = ${JSON.stringify(durations, null, 2)};\n\nexport const TOTAL_FRAMES = ${totalFrames};\n`;
  writeFileSync("remotion/narration-durations.ts", durationsCode);

  console.log(`\nTotal: ${totalFrames} frames (${(totalFrames / FPS).toFixed(1)}s)`);
  console.log("Wrote: remotion/narration-durations.ts");
  console.log("\nDone! Run 'npm run remotion:studio' to preview.");
}

main().catch((err) => {
  console.error(err);
  process.exit(1);
});
