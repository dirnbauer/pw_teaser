import { Composition } from "remotion";
import { ProductTour, SCENE_DURATIONS, LEAD_IN_FRAMES, END_CARD_FRAMES } from "./ProductTour";

const TOTAL_FRAMES =
  LEAD_IN_FRAMES +
  Object.values(SCENE_DURATIONS).reduce((sum, d) => sum + d, 0) +
  END_CARD_FRAMES;

export const RemotionRoot = () => {
  return (
    <Composition
      id="ProductTour"
      component={ProductTour}
      durationInFrames={TOTAL_FRAMES}
      fps={30}
      width={1920}
      height={1080}
    />
  );
};
