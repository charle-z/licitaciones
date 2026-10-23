import { defineConfig } from "vite";
import vue from "@vitejs/plugin-vue";
import { createHash } from "node:crypto";
import { readFileSync, writeFileSync } from "node:fs";
import { resolve } from "node:path";

function assetIntegrity() {
  let output;
  return {
    name: "asset-integrity",
    apply: "build",
    configResolved(config) {
      output = resolve(config.root, config.build.outDir);
    },
    closeBundle() {
      const hashes = {};
      for (const name of ["app.js", "app.css"]) {
        hashes[name] = "sha384-" + createHash("sha384")
          .update(readFileSync(resolve(output, name)))
          .digest("base64");
      }
      writeFileSync(resolve(output, "integrity.json"), JSON.stringify(hashes));
    },
  };
}

export default defineConfig({
  plugins: [vue(), assetIntegrity()],
  build: {
    outDir: "public/assets",
    emptyOutDir: true,
    rollupOptions: {
      input: "resources/js/main.js",
      output: {
        entryFileNames: "app.js",
        chunkFileNames: "chunks/[name]-[hash].js",
        assetFileNames: (asset) =>
          asset.names?.some((name) => name.endsWith(".css"))
            ? "app.css"
            : "[name]-[hash][extname]",
      },
    },
  },
});
