export {};

declare global {
  // eslint-disable-next-line @typescript-eslint/consistent-type-definitions
  interface Window {
    $WowheadPower: {
      refreshLinks: () => void;
    };
  }
}
