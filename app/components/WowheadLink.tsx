import type { ComponentPropsWithoutRef } from "react";
import React, { useEffect } from "react";

import { item as itemLink, itemData } from "../wowhead";

type ItemLinkProps = Omit<ComponentPropsWithoutRef<"a">, "href"> & {
  isPtr: boolean;
  item: number;
};
export const WowheadItemLink = ({
  children,
  isPtr,
  item,
  ...props
}: ItemLinkProps) => {
  useEffect(() => {
    window.$WowheadPower.refreshLinks();
  }, []);

  return (
    <a
      data-wowhead={itemData(item, isPtr)}
      href={itemLink(item, isPtr)}
      {...props}
    >
      {children}
    </a>
  );
};
