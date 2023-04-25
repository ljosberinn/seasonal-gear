const baseWowheadUrl = "https://wowhead.com/";
const ptrWowheadUrl = "https://ptr.wowhead.com/";

export const item = (id: number, isPtr = false): string => {
  if (isPtr) {
    return `${ptrWowheadUrl}item=${id}`;
  }
  return `${baseWowheadUrl}item=${id}`;
};

export const itemData = (id: number, isPtr = false): string =>
  [`item=${id}`, isPtr ? "domain=ptr" : null].filter(Boolean).join("&");
