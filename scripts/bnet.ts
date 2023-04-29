import "dotenv/config";

import { env } from "~/env/server";
import { error } from "./item-blocks/log";

export async function authenticateWithBlizzard(): Promise<string | null> {
  return fetch(
    `https://us.battle.net/oauth/token?grant_type=client_credentials&client_id=${env.BATTLE_NET_CLIENT_ID}&client_secret=${env.BATTLE_NET_CLIENT_SECRET}`,
    {
      method: "POST",
    }
  )
    .then((response) => response.json())
    .then((json) => {
      if ("access_token" in json) {
        return json.access_token;
      }

      return null;
    })
    .catch(error);
}

export async function retrieveAndParseStatsFromBlizzard(
  itemID: string,
  accessToken: string
): Promise<BlizzardItemResponse | NotFound> {
  const url = `https://us.api.blizzard.com/data/wow/item/${itemID}?namespace=static-us&locale=en_US&access_token=${accessToken}`;

  const response = await fetch(url);
  const json = await response.json();

  return json satisfies BlizzardItemResponse;
}

interface NotFound {
  code: 404;
  type: "BLZWEBAPI00000404";
  detail: "Not Found";
}

interface BlizzardItemResponse {
  _links: Links;
  id: number;
  name: string;
  quality: Quality;
  level: number;
  required_level: number;
  media: Media;
  item_class: ItemClass;
  item_subclass: ItemSubclass;
  inventory_type: InventoryType;
  purchase_price: number;
  sell_price: number;
  max_count: number;
  is_equippable: boolean;
  is_stackable: boolean;
  preview_item: PreviewItem;
  purchase_quantity: number;
}

interface Links {
  self: Self;
}

interface Self {
  href: string;
}

interface Quality {
  type: string;
  name: string;
}

interface Media {
  key: Key;
  id: number;
}

interface Key {
  href: string;
}

interface ItemClass {
  key: Key2;
  name: string;
  id: number;
}

interface Key2 {
  href: string;
}

interface ItemSubclass {
  key: Key3;
  name: string;
  id: number;
}

interface Key3 {
  href: string;
}

interface InventoryType {
  type: string;
  name: string;
}

interface PreviewItem {
  item: Item;
  sockets: Socket[];
  quality: Quality2;
  name: string;
  media: Media2;
  item_class: ItemClass2;
  item_subclass: ItemSubclass2;
  inventory_type: InventoryType2;
  binding: Binding;
  armor: Armor;
  stats?: Stat[];
  socket_bonus: string;
  sell_price: SellPrice;
  requirements: Requirements;
  level: Level2;
  durability: Durability;
}

interface Item {
  key: Key4;
  id: number;
}

interface Key4 {
  href: string;
}

interface Socket {
  socket_type: SocketType;
}

interface SocketType {
  type: string;
  name: string;
}

interface Quality2 {
  type: string;
  name: string;
}

interface Media2 {
  key: Key5;
  id: number;
}

interface Key5 {
  href: string;
}

interface ItemClass2 {
  key: Key6;
  name: string;
  id: number;
}

interface Key6 {
  href: string;
}

interface ItemSubclass2 {
  key: Key7;
  name: string;
  id: number;
}

interface Key7 {
  href: string;
}

interface InventoryType2 {
  type: string;
  name: string;
}

interface Binding {
  type: string;
  name: string;
}

interface Armor {
  value: number;
  display: Display;
}

interface Display {
  display_string: string;
  color: Color;
}

interface Color {
  r: number;
  g: number;
  b: number;
  a: number;
}

interface Stat {
  type: Type;
  value: number;
  display: Display2;
  is_negated?: boolean;
  is_equip_bonus?: boolean;
}

interface Type {
  type: string;
  name: string;
}

interface Display2 {
  display_string: string;
  color: Color2;
}

interface Color2 {
  r: number;
  g: number;
  b: number;
  a: number;
}

interface SellPrice {
  value: number;
  display_strings: DisplayStrings;
}

interface DisplayStrings {
  header: string;
  gold: string;
  silver: string;
  copper: string;
}

interface Requirements {
  level: Level;
}

interface Level {
  value: number;
  display_string: string;
}

interface Level2 {
  value: number;
  display_string: string;
}

interface Durability {
  value: number;
  display_string: string;
}
