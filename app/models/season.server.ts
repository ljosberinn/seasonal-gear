import add from "date-fns/add";

import { env } from "~/env/server";
import type { Season } from "~/seasons";
import { hasSeasonEndedForAllRegions } from "~/seasons";

const lastModified = "Last-Modified";
const cacheControl = "Cache-Control";
const eTag = "ETag";
const expires = "Expires";

type GetEnhancedSeasonParams = {
  season: Season;
};
type GetEnhancedSeasonResult = {
  season: Season;
  headers: HeadersInit;
};
export const getEnhancedSeason = async ({
  season,
}: GetEnhancedSeasonParams) => {
  const headers: HeadersInit = {};

  if (hasSeasonEndedForAllRegions(season.slug)) {
    const thirtyDays = 30 * 24 * 60 * 60;
    headers[
      cacheControl
    ] = `public, max-age=${thirtyDays}, s-maxage=${thirtyDays}, immutable`;
  }

  headers[expires] = add(new Date(), { minutes: 5 }).toUTCString();
  headers[eTag] = env.COMMIT_SHA;
  headers[lastModified] = env.BUILD_TIME;

  return { season, headers } satisfies GetEnhancedSeasonResult;
};