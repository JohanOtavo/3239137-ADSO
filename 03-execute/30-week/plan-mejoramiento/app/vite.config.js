import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

/*
 * The API base URL is read at build time from VITE_API_URL. It is not hardcoded
 * because the same bundle is served from the compose stack and from a developer
 * machine, and those two do not agree on the host.
 */
export default defineConfig({
  plugins: [react()],
  server: {
    port: 5173,
    host: true,
  },
  test: {
    environment: 'node',
    include: ['src/**/*.test.js'],
  },
});
