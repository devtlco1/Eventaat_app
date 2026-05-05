import React, { useRef, useState } from "react";
import {
  Dimensions,
  FlatList,
  Pressable,
  StyleSheet,
  Text,
  View,
  type ListRenderItem,
} from "react-native";
import { SafeAreaView } from "react-native-safe-area-context";
import { Ionicons } from "@expo/vector-icons";
import type { NativeStackScreenProps } from "@react-navigation/native-stack";
import type { AuthStackParamList } from "../navigation/AppNavigator";

const { width: W, height: H } = Dimensions.get("window");
const PURPLE = "#5B4CBD";
const ORANGE = "#EA580C";

// ── Slide data ────────────────────────────────────────────────────────────────

type Slide = {
  id: string;
  prefix: string;
  highlight: string;
  suffix: string;
  subtitle: string;
};

const SLIDES: Slide[] = [
  {
    id: "1",
    prefix: "Discover ",
    highlight: "Dining Delights\n& Exclusive Offers",
    suffix: "",
    subtitle:
      "Browse handpicked restaurants and unlock exclusive deals reserved just for you.",
  },
  {
    id: "2",
    prefix: "Build ",
    highlight: "Your Favourite\nRestaurant",
    suffix: " Collection",
    subtitle:
      "Save your go-to spots and curate a personal dining wishlist for every occasion.",
  },
  {
    id: "3",
    prefix: "Connect Instantly: ",
    highlight: "Chat\n& Call",
    suffix: " with Owners",
    subtitle:
      "Get instant answers and arrange special requests directly with restaurant owners.",
  },
];

// ── Phone mockup ──────────────────────────────────────────────────────────────

const PHONE_W = W * 0.54;
const PHONE_H = PHONE_W * 2.10;
const CORNER_R = PHONE_W * 0.14;
const SIDE_BTN_W = 4;
const SIDE_BTN_COLOR = "#3A3A3C";

/**
 * iPhone 14 Pro-style phone mockup.
 * Side buttons are rendered as absolutely-positioned siblings so they sit
 * outside the overflow:hidden chassis — prevents clipping.
 */
function PhoneMockup() {
  return (
    <View style={mockup.wrapper}>
      {/* Right-side power button */}
      <View style={[mockup.sideBtn, mockup.powerBtn]} />
      {/* Left-side volume up */}
      <View style={[mockup.sideBtn, mockup.volUp]} />
      {/* Left-side volume down */}
      <View style={[mockup.sideBtn, mockup.volDown]} />
      {/* Left-side silent toggle */}
      <View style={[mockup.sideBtn, mockup.silent]} />

      {/* Main chassis */}
      <View style={mockup.chassis}>
        <View style={mockup.dynamicIsland} />
        <View style={mockup.screen} />
      </View>
    </View>
  );
}

const mockup = StyleSheet.create({
  wrapper: {
    width: PHONE_W + SIDE_BTN_W * 2,
    height: PHONE_H,
    alignItems: "center",
  },
  chassis: {
    width: PHONE_W,
    height: PHONE_H,
    borderRadius: CORNER_R,
    backgroundColor: "#2C2C2E",
    alignItems: "center",
    paddingTop: PHONE_W * 0.06,
    overflow: "hidden",
    shadowColor: "#000",
    shadowOffset: { width: 0, height: 10 },
    shadowOpacity: 0.22,
    shadowRadius: 28,
    elevation: 12,
  },
  dynamicIsland: {
    width: PHONE_W * 0.34,
    height: PHONE_W * 0.068,
    borderRadius: 999,
    backgroundColor: "#1A1A1A",
    marginBottom: PHONE_W * 0.04,
  },
  screen: {
    flex: 1,
    width: PHONE_W - 6,
    borderRadius: PHONE_W * 0.12,
    backgroundColor: "#E4E4E6",
  },
  // Side buttons — positioned relative to wrapper
  sideBtn: {
    position: "absolute",
    width: SIDE_BTN_W,
    borderRadius: 2,
    backgroundColor: SIDE_BTN_COLOR,
  },
  // Power button — right side, mid-height
  powerBtn: {
    right: 0,
    top: PHONE_H * 0.30,
    height: PHONE_H * 0.12,
  },
  // Volume up — left side
  volUp: {
    left: 0,
    top: PHONE_H * 0.22,
    height: PHONE_H * 0.08,
  },
  // Volume down — left side
  volDown: {
    left: 0,
    top: PHONE_H * 0.32,
    height: PHONE_H * 0.08,
  },
  // Silent toggle — left side
  silent: {
    left: 0,
    top: PHONE_H * 0.14,
    height: PHONE_H * 0.045,
  },
});

// ── Dot indicator ─────────────────────────────────────────────────────────────

function Dots({ count, active }: { count: number; active: number }) {
  return (
    <View style={dots.row}>
      {Array.from({ length: count }, (_, i) => (
        <View
          key={i}
          style={[dots.dot, i === active ? dots.dotActive : dots.dotInactive]}
        />
      ))}
    </View>
  );
}

const dots = StyleSheet.create({
  row: { flexDirection: "row", gap: 8, alignItems: "center" },
  dot: { width: 10, height: 10, borderRadius: 5 },
  dotActive: { backgroundColor: PURPLE },
  dotInactive: { backgroundColor: "#D1D5DB" },
});

// ── Illustration height & curve ───────────────────────────────────────────────

const ILLUS_H = H * 0.54;
const ILLUS_CURVE = W * 0.22;

// ── Main screen ───────────────────────────────────────────────────────────────

type Props = NativeStackScreenProps<AuthStackParamList, "Onboarding">;

export function OnboardingScreen({ navigation }: Props) {
  const [activeIndex, setActiveIndex] = useState(0);
  const flatRef = useRef<FlatList<Slide>>(null);

  const goTo = (index: number) => {
    flatRef.current?.scrollToIndex({ index, animated: true });
    setActiveIndex(index);
  };

  const onNext = () => {
    if (activeIndex < SLIDES.length - 1) {
      goTo(activeIndex + 1);
    } else {
      navigation.navigate("SignUp");
    }
  };

  const onBack = () => {
    if (activeIndex > 0) goTo(activeIndex - 1);
  };

  const onSkip = () => navigation.navigate("SignUp");

  const renderItem: ListRenderItem<Slide> = ({ item }) => (
    <View style={slide.container}>
      {/* ── Illustration area — gray rounded panel ── */}
      <View style={slide.illustrationArea}>
        <PhoneMockup />
      </View>

      {/* ── Text area ── */}
      <View style={slide.textArea}>
        <Text style={slide.title}>
          {item.prefix}
          <Text style={slide.titleOrange}>{item.highlight}</Text>
          {item.suffix}
        </Text>
        <Text style={slide.subtitle}>{item.subtitle}</Text>
      </View>
    </View>
  );

  return (
    <SafeAreaView style={styles.safe} edges={["top", "bottom"]}>
      {/* Skip button — top right (not on last slide) */}
      {activeIndex < SLIDES.length - 1 ? (
        <Pressable style={styles.skipBtn} onPress={onSkip} hitSlop={12}>
          <Text style={styles.skipText}>Skip</Text>
        </Pressable>
      ) : (
        <View style={styles.skipBtn} />
      )}

      {/* Slides */}
      <FlatList
        ref={flatRef}
        data={SLIDES}
        keyExtractor={(s) => s.id}
        renderItem={renderItem}
        horizontal
        pagingEnabled
        showsHorizontalScrollIndicator={false}
        scrollEnabled={false}
        style={styles.flatList}
      />

      {/* Bottom navigation row */}
      <View style={styles.navRow}>
        {/* Back button (hidden on first slide) */}
        {activeIndex > 0 ? (
          <Pressable style={[styles.navBtn, styles.navBtnOutline]} onPress={onBack}>
            <Ionicons name="arrow-back" size={22} color={PURPLE} />
          </Pressable>
        ) : (
          <View style={styles.navBtnPlaceholder} />
        )}

        <Dots count={SLIDES.length} active={activeIndex} />

        {/* Next / Get started button */}
        <Pressable style={[styles.navBtn, styles.navBtnFilled]} onPress={onNext}>
          <Ionicons name="arrow-forward" size={22} color="#FFFFFF" />
        </Pressable>
      </View>
    </SafeAreaView>
  );
}

// ── Styles ────────────────────────────────────────────────────────────────────

const slide = StyleSheet.create({
  container: { width: W },
  illustrationArea: {
    height: ILLUS_H,
    backgroundColor: "#F5F5F5",
    alignItems: "center",
    justifyContent: "flex-start",
    paddingTop: 20,
    borderBottomLeftRadius: ILLUS_CURVE,
    borderBottomRightRadius: ILLUS_CURVE,
    // Note: no overflow:hidden so phone side buttons are not clipped
  },
  textArea: {
    paddingHorizontal: 28,
    paddingTop: 28,
    gap: 12,
  },
  title: {
    fontSize: 26,
    fontWeight: "800",
    color: "#111827",
    lineHeight: 36,
    letterSpacing: -0.4,
    textAlign: "center",
  },
  titleOrange: { color: ORANGE },
  subtitle: {
    fontSize: 14,
    lineHeight: 22,
    color: "#9CA3AF",
    textAlign: "center",
  },
});

const styles = StyleSheet.create({
  safe: { flex: 1, backgroundColor: "#FFFFFF" },
  skipBtn: {
    alignSelf: "flex-end",
    paddingHorizontal: 20,
    paddingVertical: 12,
    minWidth: 60,
    alignItems: "flex-end",
  },
  skipText: {
    fontSize: 15,
    fontWeight: "500",
    color: PURPLE,
  },
  flatList: { flexGrow: 0 },
  navRow: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
    paddingHorizontal: 24,
    paddingVertical: 24,
    marginTop: "auto",
  },
  navBtn: {
    width: 52,
    height: 52,
    borderRadius: 26,
    alignItems: "center",
    justifyContent: "center",
  },
  navBtnFilled: { backgroundColor: PURPLE },
  navBtnOutline: {
    borderWidth: 1.5,
    borderColor: PURPLE,
    backgroundColor: "transparent",
  },
  navBtnPlaceholder: { width: 52, height: 52 },
});
