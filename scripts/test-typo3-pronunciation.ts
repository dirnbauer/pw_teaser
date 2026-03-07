const { writeFileSync, mkdirSync, existsSync } = require("fs");

const VOICE_ID = "onwK4e9ZLuTAKqWW03F9";
const MODEL_ID = "eleven_multilingual_v2";
const OUT_DIR = "out/pronunciation-tests";

const VARIANTS: Record<string, string> = {
  "01-typo-three": "TYPO three",
  "02-typo3": "TYPO3",
  "03-typo-three-hyphen": "typo-three",
  "04-tüpo-three": "Tüpo three",
  "05-tipo-three": "Tipo three",
  "06-taipo-three": "Taipo three",
  "07-typo3-sentence": "TYPO3 is a content management system.",
  "08-typo-three-sentence": "TYPO three is a content management system.",
  "09-taipo-three-sentence": "Taipo three is a content management system.",
};

async function generate(name: string, text: string): Promise<void> {
  const apiKey = process.env.ELEVENLABS_API_KEY;
  if (!apiKey) {
    throw new Error("ELEVENLABS_API_KEY not set");
  }

  console.log(`  ${name}: "${text}"`);

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
        text,
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
    throw new Error(`ElevenLabs error for "${name}": ${err}`);
  }

  const buf = Buffer.from(await response.arrayBuffer());
  writeFileSync(`${OUT_DIR}/${name}.mp3`, buf);
  console.log(`    -> ${OUT_DIR}/${name}.mp3`);
}

async function main() {
  console.log("TYPO3 pronunciation test\n");

  if (!existsSync(OUT_DIR)) {
    mkdirSync(OUT_DIR, { recursive: true });
  }

  for (const [name, text] of Object.entries(VARIANTS)) {
    await generate(name, text);
  }

  console.log(`\nDone! Listen to files in ${OUT_DIR}/`);
}

main().catch((err) => {
  console.error(err);
  process.exit(1);
});
