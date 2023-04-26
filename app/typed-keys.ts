export function typedKeys<T extends object>(
  obj: T | null | undefined
): (keyof T)[] {
  if (obj === null || obj === undefined) {
    return [];
  }
  return Object.keys(obj) as (keyof typeof obj)[];
}
