import { redirect } from "@remix-run/node";
import { Outlet } from "@remix-run/react";

import { findSeasonByName } from "~/seasons";

export const loader = () => {
  const latest = findSeasonByName("latest");

  if (!latest) {
    throw new Error("Couldn't determine latest season.");
  }

  return redirect(`/${latest.slug}`, 307);
};

export default function Index() {
  return <Outlet />;
}
