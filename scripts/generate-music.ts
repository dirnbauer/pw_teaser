const { writeFileSync, mkdirSync, existsSync } = require("fs");
const { resolve } = require("path");

const BASE_URL = "https://apibox.erweima.ai/api/v1";
const OUT_DIR = resolve(__dirname, "../public/music");
const CONFIG_FILE = resolve(__dirname, "../remotion/music-config.ts");
const OUT_FILE = resolve(OUT_DIR, "background.mp3");

const MUSIC_PROMPT = `Uplifting neoclassical underscore in a bright major key for a modern
technology product video. Energetic piano arpeggios over warm pizzicato
strings and an optimistic cello melody. Forward-moving and engaging —
like the opening of an Apple keynote. Strictly major key harmonies only,
no minor chords, no melancholic or moll passages whatsoever. Every
progression should feel bright, confident, and resolving upward.
Subtle modern electronic texture beneath the classical instruments.
No heavy drums, no vocals, no sudden drops.
Tempo around 110 BPM for the main section. Engaging background music
that lifts the mood without competing with narration.
The outro should transition to a gentle, soft, slow solo piano —
still in a major key, warm and reflective but never sad or minor.
Think a bright, tender piano coda that feels like a gentle landing.
Think Ludovico Einaudi meets Ólafur Arnalds — optimistic, purposeful,
never sad. Duration should be around 4 minutes.`;

const MUSIC_STYLE = "Neoclassical, Cinematic, Uplifting, Major Key, Strings";
const MUSIC_TITLE = "pw_teaser — Product Tour";

async function sunoGenerate(apiKey: string): Promise<string> {
  console.log("  Submitting music generation request to Suno...");

  const res = await fetch(`${BASE_URL}/generate`, {
    method: "POST",
    headers: {
      Authorization: `Bearer ${apiKey}`,
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      prompt: MUSIC_PROMPT,
      customMode: true,
      style: MUSIC_STYLE,
      title: MUSIC_TITLE,
      instrumental: true,
      model: "V4_5ALL",
      callBackUrl: "https://httpbin.org/post",
    }),
  });

  if (!res.ok) {
    const body = await res.text();
    throw new Error(`Suno API error: ${res.status} — ${body}`);
  }

  const json = await res.json();
  if (json.code !== 200) {
    throw new Error(`Suno API error: ${json.msg}`);
  }

  const taskId = json.data.taskId;
  console.log(`  Task submitted: ${taskId}`);
  return taskId;
}

async function sunoWaitForCompletion(
  apiKey: string,
  taskId: string,
  maxWaitMs = 600_000,
): Promise<{ audioUrl: string; duration: number }> {
  const start = Date.now();
  let attempt = 0;

  while (Date.now() - start < maxWaitMs) {
    attempt++;
    const res = await fetch(
      `${BASE_URL}/generate/record-info?taskId=${taskId}`,
      { headers: { Authorization: `Bearer ${apiKey}` } },
    );

    const json = await res.json();
    const status = json.data?.status;
    const tracks = json.data?.response?.sunoData ?? [];

    if (status === "SUCCESS" || status === "FIRST_SUCCESS") {
      const track = tracks.find((t: any) => t.audioUrl);
      if (!track) {
        throw new Error("Suno returned SUCCESS but no audio URL");
      }
      console.log(`  Generation complete after ${attempt} polls`);
      console.log(`    Title: ${track.title}`);
      console.log(`    Duration: ${track.duration?.toFixed(1) ?? "unknown"}s`);
      return { audioUrl: track.audioUrl, duration: track.duration ?? 0 };
    }

    if (status === "FAILED") {
      throw new Error(
        `Suno generation failed: ${json.data.errorMessage || "unknown error"}`,
      );
    }

    const waitSec = attempt <= 3 ? 15 : 30;
    console.log(
      `  Status: ${status} — checking again in ${waitSec}s (attempt ${attempt})`,
    );
    await new Promise((r) => setTimeout(r, waitSec * 1000));
  }

  throw new Error(`Suno generation timed out after ${maxWaitMs / 1000}s`);
}

async function downloadFile(url: string, dest: string) {
  console.log(`  Downloading → ${dest}`);
  const res = await fetch(url);
  if (!res.ok) throw new Error(`Download failed: ${res.status}`);
  const buffer = Buffer.from(await res.arrayBuffer());
  writeFileSync(dest, buffer);
  console.log(`  Saved (${(buffer.length / 1024).toFixed(0)} KB)`);
}

function writeMusicConfig(exists: boolean, durationSeconds: number) {
  const content = `export const HAS_BACKGROUND_MUSIC = ${exists};\nexport const MUSIC_DURATION_SECONDS = ${durationSeconds.toFixed(1)};\n`;
  writeFileSync(CONFIG_FILE, content);
  console.log(`  Updated remotion/music-config.ts`);
}

async function main() {
  console.log("\npw_teaser — Background Music Generator\n");

  const apiKey = process.env.SUNO_API_KEY;
  if (!apiKey) {
    console.error("Error: SUNO_API_KEY not set.");
    if (existsSync(OUT_FILE)) {
      console.log("  Existing background.mp3 found — keeping it.\n");
      writeMusicConfig(true, 0);
    } else {
      writeMusicConfig(false, 0);
    }
    process.exit(1);
  }

  if (!existsSync(OUT_DIR)) {
    mkdirSync(OUT_DIR, { recursive: true });
  }

  try {
    const creditsRes = await fetch(`${BASE_URL}/get-credits`, {
      headers: { Authorization: `Bearer ${apiKey}` },
    });
    const creditsJson = await creditsRes.json();
    console.log(`  Suno credits remaining: ${creditsJson.data?.credits ?? "unknown"}\n`);
  } catch {
    console.log("  Could not check credits (non-fatal)\n");
  }

  const taskId = await sunoGenerate(apiKey);
  const { audioUrl, duration } = await sunoWaitForCompletion(apiKey, taskId);

  await downloadFile(audioUrl, OUT_FILE);
  writeMusicConfig(true, duration);

  console.log("\nBackground music saved to public/music/background.mp3\n");
}

main().catch((err) => {
  console.error("Fatal:", err);
  process.exit(1);
});
