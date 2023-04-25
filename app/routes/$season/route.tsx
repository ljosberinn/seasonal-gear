import type { LoaderArgs } from "@remix-run/node";
import { json } from "@remix-run/node";
import { useLoaderData } from "@remix-run/react";

import { WowheadItemLink } from "~/components/WowheadLink";
import { getEnhancedSeason } from "~/models/season.server";
import { Footer } from "~/routes/$season/Footer";
import { Header } from "~/routes/$season/Header";
import { findSeasonByName } from "~/seasons";

export const loader = async ({ params }: LoaderArgs) => {
  if (!("season" in params) || !params.season) {
    throw new Response(undefined, {
      status: 400,
      statusText: "Missing params.",
    });
  }

  const season = findSeasonByName(params.season);

  if (!season) {
    throw new Response(undefined, {
      status: 400,
      statusText: "Unknown season.",
    });
  }

  const { season: enhancedSeason, headers } = await getEnhancedSeason({
    season,
  });

  return json(enhancedSeason, headers);
};

export default function Season() {
  const season = useLoaderData<typeof loader>();

  return (
    <>
      <Header />
      <main className="container mt-4 flex max-w-screen-2xl flex-1 flex-col space-y-4 px-4 md:mx-auto 2xl:px-0">
        <WowheadItemLink isPtr={season.usePtrTooltip} item={200342}>
          Skybound Avenger&apos;s Harness
        </WowheadItemLink>
      </main>
      <Footer />
    </>
  );
}
