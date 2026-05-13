import { defaultConfig } from "@tamagui/config/v5";
import { createAnimations } from "@tamagui/animations-react-native";
import { createTamagui } from "tamagui";

const animations = createAnimations({
  quick: {
    type: "spring",
    damping: 20,
    mass: 1.2,
    stiffness: 250,
  },
  lazy: {
    type: "spring",
    damping: 22,
    stiffness: 120,
  },
  medium: {
    type: "spring",
    damping: 15,
    stiffness: 120,
    mass: 1,
  },
  bouncy: {
    type: "spring",
    damping: 9,
    mass: 0.9,
    stiffness: 150,
  },
});

export const tamaguiConfig = createTamagui({
  ...defaultConfig,
  animations,
});

export default tamaguiConfig;

export type Conf = typeof tamaguiConfig;

declare module "tamagui" {
  interface TamaguiCustomConfig extends Conf {}
}
