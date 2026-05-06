import { Dimensions } from "react-native";

const DESIGN_W = 375;
const DESIGN_H = 812;

/**
 * Computes layout values to render a 375×812 design artboard responsively
 * on any screen size using cover-style scaling.
 *
 * scale = max(screenW/375, screenH/812) guarantees the artboard always fills
 * the full screen; excess is distributed symmetrically (centred crop).
 *
 * Usage:
 *   const ab = artboard();
 *   <Image style={ab.imageStyle} resizeMode="stretch" />
 *   <Pressable style={ab.rect(24, 585, 327, 52)} />
 */
export function artboard() {
  const { width: sw, height: sh } = Dimensions.get("window");
  const scale = Math.max(sw / DESIGN_W, sh / DESIGN_H);
  const aw = DESIGN_W * scale;
  const ah = DESIGN_H * scale;
  const al = (sw - aw) / 2;
  const at = (sh - ah) / 2;

  return {
    /** Absolute-fill style that sizes and centres the artboard image. */
    imageStyle: {
      position: "absolute" as const,
      left: al,
      top: at,
      width: aw,
      height: ah,
    },

    /**
     * Convert a rectangle in 375×812 design coordinates to absolute screen
     * coordinates, accounting for the artboard offset and scale.
     *
     * @param dx  left edge in design pixels (0–375)
     * @param dy  top  edge in design pixels (0–812)
     * @param dw  width  in design pixels
     * @param dh  height in design pixels
     */
    rect(dx: number, dy: number, dw: number, dh: number) {
      return {
        position: "absolute" as const,
        left: al + dx * scale,
        top: at + dy * scale,
        width: dw * scale,
        height: dh * scale,
      };
    },
  };
}
