import { Tabs } from "expo-router";
import FloatingTabBar from "@/components/floating-tab-bar";

export default function TabLayout() {
  return (
    <Tabs
      tabBar={(props) => <FloatingTabBar {...props} />}
      screenOptions={{
        headerShown: false,
      }}
    >
      <Tabs.Screen name="index" options={{ title: "Dashboard" }} />
      <Tabs.Screen name="orders" options={{ title: "Order" }} />
      <Tabs.Screen name="products" options={{ title: "Product" }} />
      <Tabs.Screen name="earning" options={{ title: "Earning" }} />
      <Tabs.Screen name="account" options={{ title: "Profile" }} />
    </Tabs>
  );
}
