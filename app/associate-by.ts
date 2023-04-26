export function associateBy<T>(
  arr: T[],
  predicate: (value: T, index: number, arr: T[]) => string
): Record<string, T[]> {
  return arr.reduce<Record<string, T[]>>((map, value, index, array) => {
    const key = predicate(value, index, array);
    map[key] = map[key]?.concat([value]) ?? [value];
    return map;
  }, {});
}
