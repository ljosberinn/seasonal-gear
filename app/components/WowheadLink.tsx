import { type ComponentPropsWithoutRef } from "react";

import { item as itemLink, itemData } from "../wowhead";

type ItemLinkProps = Omit<ComponentPropsWithoutRef<"a">, "href"> & {
  isPtr: boolean;
  item: number;
};
export function WowheadItemLink({
  children,
  isPtr,
  item,
  ...props
}: ItemLinkProps): JSX.Element {
  return (
    <a
      data-wowhead={itemData(item, isPtr)}
      href={itemLink(item, isPtr)}
      {...props}
    >
      {children}
    </a>
  );
}
