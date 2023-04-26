import { json, type LoaderArgs, type TypedResponse } from "@remix-run/node";
import { useLoaderData } from "@remix-run/react";

import { WowheadItemLink } from "~/components/WowheadLink";
import { itemsByJournalInstanceNameAndJournalEncounterName } from "~/models/items";
import { getEnhancedSeason } from "~/models/season.server";
import { Footer } from "~/routes/$season/Footer";
import { Header } from "~/routes/$season/Header";
import { findSeasonByName, type Season as SeasonType } from "~/seasons";
import { typedKeys } from "~/typed-keys";

export const loader = ({ params }: LoaderArgs): TypedResponse<SeasonType> => {
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

  const { season: enhancedSeason, headers } = getEnhancedSeason({
    season,
  });

  return json(enhancedSeason, headers);
};

export default function Season(): JSX.Element {
  const season = useLoaderData<typeof loader>();

  return (
    <>
      <Header />
      <main className="container mt-4 flex max-w-screen-2xl flex-1 flex-col space-y-4 px-4 md:mx-auto 2xl:px-0">
        <h1 className="text-lg">
          Items by Journal Instance and Journal Encounter
        </h1>
        {typedKeys(itemsByJournalInstanceNameAndJournalEncounterName).map(
          (instanceName) => (
            <section className="space-y-1" key={instanceName}>
              <h2 className="font-bold">{instanceName}</h2>
              {typedKeys(
                itemsByJournalInstanceNameAndJournalEncounterName[instanceName]
              ).map((encounterName) => (
                <section key={encounterName}>
                  <h3 className="italic">{encounterName}</h3>
                  <ul>
                    {itemsByJournalInstanceNameAndJournalEncounterName[
                      instanceName
                    ][encounterName].map((item) => (
                      <li key={item.id}>
                        <WowheadItemLink
                          isPtr={season.usePtrTooltip}
                          item={item.id}
                        >
                          {item.name}
                        </WowheadItemLink>
                      </li>
                    ))}
                  </ul>
                </section>
              ))}
            </section>
          )
        )}
      </main>
      <Footer />
    </>
  );
}
