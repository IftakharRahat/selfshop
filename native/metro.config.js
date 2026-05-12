// Learn more https://docs.expo.io/guides/customizing-metro
const { getDefaultConfig } = require("expo/metro-config");
const { withTamagui } = require("@tamagui/metro-plugin");
const { withSentryConfig } = require("@sentry/react-native/metro");

const config = getDefaultConfig(__dirname);

config.resolver.unstable_enablePackageExports = true;

const tamaguiConfig = withTamagui(config, {
  components: ["tamagui"],
  config: "./tamagui.config.ts",
});

module.exports = withSentryConfig(tamaguiConfig);
