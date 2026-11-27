/**
 * RESET Store — Single Source of Truth API Contract (v1)
 *
 * Strictly typed contract shared between:
 *  - Unified `Website Project` Laravel 13 REST API (`/api/v1/*`, `/api/openapi.json`, `/api/docs`)
 *  - Pure-Flutter `Mobile Project` 3.35.1 / Dart 3.9.0 Client SDK (`Mobile Project/lib/core/api/api_client.dart` + `Mobile Project/lib/models/*`)
 *
 * CRITICAL DOMAIN CONVENTIONS:
 *  1. Every `*_cents` integer field stores BASE CAMBODIAN RIEL (KHR ៛), NOT USD cents.
 *     Example: `200000` = `200,000 ៛`. USD display divides base KHR by `exchange_rate` (default 4100).
 *  2. Standard delivery is `0 ៛` across all 25 Cambodian provinces.
 *     Express delivery is strictly `12,000 ៛` ONLY when `province === 'Phnom Penh'`.
 *  3. No `any` types are permitted anywhere in this contract.
 */

// ============================================================================
// 1. PROTOCOL, HEADERS & ROUTE CONSTANTS
// ============================================================================

export const API_BASE_PREFIX = '/api/v1' as const;
export const OPENAPI_SPEC_PATH = '/api/openapi.json' as const;
export const SWAGGER_DOCS_PATH = '/api/docs' as const;

export const API_HEADERS = {
  AUTHORIZATION: 'Authorization',
  CART_TOKEN: 'X-Cart-Token',
  ORDER_TOKEN: 'X-Order-Token',
  LOCALE: 'X-Locale',
  ACCEPT_LANGUAGE: 'Accept-Language',
  CURRENCY: 'X-Currency',
  ACCEPT: 'Accept',
  CONTENT_TYPE: 'Content-Type',
} as const;

export const API_ROUTES = {
  // OpenAPI & Docs
  OPENAPI_JSON: OPENAPI_SPEC_PATH,
  SWAGGER_DOCS: SWAGGER_DOCS_PATH,

  // Store Bootstrap & Catalog
  BOOTSTRAP: `${API_BASE_PREFIX}/bootstrap`,
  HOME: `${API_BASE_PREFIX}/home`,
  PRODUCTS: `${API_BASE_PREFIX}/products`,
  SEARCH_SUGGEST: `${API_BASE_PREFIX}/search/suggest`,
  PRODUCT_DETAIL: (slug: string): string => `${API_BASE_PREFIX}/products/${slug}`,
  PRODUCT_REVIEWS: (slug: string): string => `${API_BASE_PREFIX}/products/${slug}/reviews`,

  // Wishlist (Auth)
  WISHLIST: `${API_BASE_PREFIX}/wishlist`,
  WISHLIST_TOGGLE: `${API_BASE_PREFIX}/wishlist/toggle`,

  // Cart (Auth Bearer or Guest X-Cart-Token)
  CART: `${API_BASE_PREFIX}/cart`,
  CART_ITEMS: `${API_BASE_PREFIX}/cart/items`,
  CART_ITEM: (id: number): string => `${API_BASE_PREFIX}/cart/items/${id}`,
  CART_DISCOUNT: `${API_BASE_PREFIX}/cart/discount`,

  // Checkout, Bakong KHQR Payment & Invoice (Auth Owner/Admin or Guest X-Order-Token)
  CHECKOUT: `${API_BASE_PREFIX}/checkout`,
  ORDER_PAYMENT: (orderNumber: string): string => `${API_BASE_PREFIX}/orders/${orderNumber}/payment`,
  PAYMENT_STATUS: (paymentId: number): string => `${API_BASE_PREFIX}/payments/${paymentId}/status`,
  ORDER_REGENERATE_QR: (orderNumber: string): string => `${API_BASE_PREFIX}/orders/${orderNumber}/regenerate-qr`,
  PAYMENT_SIMULATE_PAY: (paymentId: number): string => `${API_BASE_PREFIX}/payments/${paymentId}/simulate-pay`,
  ORDER_INVOICE: (orderNumber: string): string => `${API_BASE_PREFIX}/orders/${orderNumber}/invoice`,

  // Authentication
  AUTH_REGISTER: `${API_BASE_PREFIX}/auth/register`,
  AUTH_LOGIN: `${API_BASE_PREFIX}/auth/login`,
  AUTH_LOGOUT: `${API_BASE_PREFIX}/auth/logout`,
  AUTH_FORGOT_PASSWORD: `${API_BASE_PREFIX}/auth/forgot-password`,
  AUTH_ME: `${API_BASE_PREFIX}/auth/me`,
  AUTH_PASSWORD: `${API_BASE_PREFIX}/auth/password`,

  // Customer Account Portal (Auth)
  ACCOUNT_PROFILE: `${API_BASE_PREFIX}/account/profile`,
  ACCOUNT_ORDERS: `${API_BASE_PREFIX}/account/orders`,
  ACCOUNT_ORDER_DETAIL: (orderNumber: string): string => `${API_BASE_PREFIX}/account/orders/${orderNumber}`,
  ACCOUNT_ADDRESSES: `${API_BASE_PREFIX}/account/addresses`,
  ACCOUNT_ADDRESS: (addressId: number): string => `${API_BASE_PREFIX}/account/addresses/${addressId}`,

  // Info Pages & Support
  PAGE_DETAIL: (slug: InfoPageSlug): string => `${API_BASE_PREFIX}/pages/${slug}`,
  CONTACT: `${API_BASE_PREFIX}/contact`,
  NEWSLETTER: `${API_BASE_PREFIX}/newsletter`,
} as const;

export const HTTP_STATUS = {
  OK: 200,
  CREATED: 201,
  BAD_REQUEST: 400,
  UNAUTHORIZED: 401,
  FORBIDDEN: 403,
  NOT_FOUND: 404,
  UNPROCESSABLE_ENTITY: 422,
  TOO_MANY_REQUESTS: 429,
  INTERNAL_SERVER_ERROR: 500,
} as const;

export type HttpStatusCode = (typeof HTTP_STATUS)[keyof typeof HTTP_STATUS];

// ============================================================================
// 2. CORE ENUMS & PRIMITIVE UNIONS
// ============================================================================

export type Locale = 'en' | 'km';
export type Currency = 'KHR' | 'USD';
export type UserRole = 'admin' | 'staff' | 'customer';
export type ShippingMethod = 'standard' | 'express';
export type ProductSort = 'featured' | 'newest' | 'price_low' | 'price_high';
export type DiscountType = 'percentage' | 'fixed_amount' | 'free_shipping';

export type OrderStatus =
  | 'pending_payment'
  | 'paid'
  | 'packed'
  | 'shipped'
  | 'delivered'
  | 'cancelled'
  | 'expired'
  | 'refunded';

export type PaymentStatus = 'pending' | 'paid' | 'expired' | 'failed';

export type InfoPageSlug =
  | 'about'
  | 'faq'
  | 'shipping'
  | 'privacy'
  | 'terms'
  | 'size-guide';

export type ProductDetailTabKey =
  | 'description'
  | 'fabric'
  | 'care'
  | 'fit'
  | 'shipping';

export type ApiErrorCode =
  | 'UNAUTHENTICATED'
  | 'FORBIDDEN'
  | 'NOT_FOUND'
  | 'VALIDATION_ERROR'
  | 'INSUFFICIENT_STOCK'
  | 'INVALID_DISCOUNT'
  | 'CHECKOUT_LOCKED'
  | 'MOCK_PAYMENT_DISABLED'
  | 'PAYMENT_ALREADY_PAID'
  | 'RATE_LIMITED'
  | 'SERVER_ERROR';

// ============================================================================
// 3. STANDARD REQUEST HEADERS & ERROR / PAGINATION ENVELOPES
// ============================================================================

export interface ApiContextHeaders {
  Authorization?: `Bearer ${string}`;
  'X-Cart-Token'?: string;
  'X-Order-Token'?: string;
  'X-Locale'?: Locale;
  'Accept-Language'?: Locale;
  'X-Currency'?: Currency;
}

export interface ApiErrorResponse {
  success: false;
  message: string;
  code?: ApiErrorCode;
  errors?: Record<string, string[]>;
}

export interface ApiValidationErrorResponse extends ApiErrorResponse {
  code?: 'VALIDATION_ERROR' | 'INSUFFICIENT_STOCK' | 'INVALID_DISCOUNT';
  errors: Record<string, string[]>;
}

export interface PaginationMeta {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number | null;
  to: number | null;
  has_more_pages: boolean;
}

export interface PaginatedResponse<TItem> {
  data: TItem[];
  meta: PaginationMeta;
}

// ============================================================================
// 4. DOMAIN DATA TRANSFER OBJECTS (DTOs)
// ============================================================================

export interface UserDto {
  id: number;
  name: string;
  email: string;
  phone: string | null;
  role: UserRole;
  email_verified_at: string | null;
  created_at: string;
}

export interface AddressDto {
  id: number;
  type: 'shipping' | 'billing';
  first_name: string;
  last_name: string;
  full_name: string;
  phone: string;
  province: string;
  district: string | null;
  commune: string | null;
  street_address: string;
  postal_code: string | null;
  formatted_address: string;
  is_default: boolean;
  created_at: string;
  updated_at: string;
}

export interface CategorySummaryDto {
  id: number;
  name: string;
  name_en: string;
  name_km: string | null;
  slug: string;
}

export interface CategoryDto extends CategorySummaryDto {
  description: string | null;
  description_en: string | null;
  description_km: string | null;
  image_url: string | null;
  sort_order: number;
  products_count?: number;
}

export interface ProductImageDto {
  id: number;
  image_url: string;
  alt_text: string | null;
  is_primary: boolean;
  sort_order: number;
}

export interface ProductColorSwatchDto {
  name: string;
  code: string;
}

export interface ProductVariantDto {
  id: number;
  product_id: number;
  sku: string;
  size: string;
  color: string;
  color_code: string;
  label: string;
  price_cents: number;
  formatted_price: string;
  converted_price: number;
  stock_quantity: number;
  reserved_quantity: number;
  available_stock: number;
  is_in_stock: boolean;
}

export interface ProductDetailsRawDto {
  fabric: string | null;
  care: string | null;
  fit: string | null;
  shipping: string | null;
}

export interface ProductDetailTabDto {
  key: ProductDetailTabKey;
  title: string;
  content: string;
}

export interface ReviewDto {
  id: number;
  product_id: number;
  user_id: number;
  author_name: string;
  rating: 1 | 2 | 3 | 4 | 5;
  title: string | null;
  body: string;
  is_verified_purchase: boolean;
  created_at: string;
  formatted_date: string;
}

export interface ProductCardDto {
  id: number;
  name: string;
  name_en: string;
  name_km: string | null;
  slug: string;
  sku_prefix: string;
  tagline: string | null;
  tagline_en: string | null;
  tagline_km: string | null;
  category: CategorySummaryDto | null;
  primary_image_url: string;
  base_price_cents: number;
  compare_at_price_cents: number | null;
  formatted_price: string;
  formatted_compare_at_price: string | null;
  converted_price: number;
  converted_compare_at_price: number | null;
  is_on_sale: boolean;
  is_featured: boolean;
  is_new_arrival: boolean;
  is_best_seller: boolean;
  is_in_stock: boolean;
  total_stock: number;
  average_rating: number;
  review_count: number;
  colors: ProductColorSwatchDto[];
}

export interface ProductDetailDto extends ProductCardDto {
  description: string;
  description_en: string;
  description_km: string | null;
  images: ProductImageDto[];
  variants: ProductVariantDto[];
  available_sizes: string[];
  available_colors: ProductColorSwatchDto[];
  details: ProductDetailsRawDto;
  detail_tabs: ProductDetailTabDto[];
  reviews: ReviewDto[];
}

export interface CartItemDto {
  id: number;
  product_id: number;
  product_slug: string;
  product_variant_id: number;
  product_name: string;
  product_name_en: string;
  product_name_km: string | null;
  variant_label: string;
  sku: string;
  size: string;
  color: string;
  color_code: string;
  image_url: string;
  price_cents: number;
  formatted_price: string;
  converted_price: number;
  quantity: number;
  max_stock: number;
  subtotal_cents: number;
  formatted_subtotal: string;
  converted_subtotal: number;
}

export interface CartDiscountDto {
  id: number;
  code: string;
  type: DiscountType;
  value: number;
  formatted_value: string;
  min_spend_cents: number;
}

export interface CartTotalsDto {
  currency: Currency;
  exchange_rate: number;
  province: string;
  shipping_method: ShippingMethod;
  subtotal_cents: number;
  formatted_subtotal: string;
  discount_cents: number;
  formatted_discount: string;
  shipping_fee_cents: number;
  formatted_shipping: string;
  total_cents: number;
  formatted_total: string;
  converted_total: number;
  free_shipping_threshold_cents: number;
  formatted_free_shipping_threshold: string;
  free_shipping_qualified: boolean;
  free_shipping_remaining_cents: number;
  free_shipping_remaining_formatted: string;
  free_shipping_progress_percent: number;
}

export interface CartPayloadDto extends CartTotalsDto {
  cart_token: string | null;
  item_count: number;
  items: CartItemDto[];
  discount_code: string | null;
  discount: CartDiscountDto | null;
}

export interface OrderShippingAddressDto {
  name: string;
  full_name: string;
  first_name: string;
  last_name: string;
  phone: string;
  province: string;
  district: string | null;
  commune: string | null;
  street_address: string;
}

export interface OrderItemDto {
  id: number;
  product_variant_id: number | null;
  product_slug: string | null;
  image_url: string | null;
  product_name: string;
  product_name_en: string;
  product_name_km: string | null;
  variant_label: string;
  sku: string;
  price_cents: number;
  formatted_price: string;
  quantity: number;
  subtotal_cents: number;
  formatted_subtotal: string;
}

export interface OrderStatusStepDto {
  key: 'pending_payment' | 'paid' | 'packed' | 'shipped' | 'delivered';
  label: string;
  completed: boolean;
  active: boolean;
  timestamp: string | null;
}

export interface PaymentDto {
  id: number;
  order_id: number;
  order_number: string;
  provider: 'bakong_khqr';
  md5: string;
  qr_string: string;
  account_id: string;
  merchant_name: string;
  amount: number;
  currency: Currency;
  formatted_amount: string;
  khr_amount: number;
  formatted_khr_amount: string;
  usd_amount: number;
  formatted_usd_amount: string;
  exchange_rate: number;
  status: PaymentStatus;
  status_label: string;
  expires_at: string;
  time_remaining_seconds: number;
  paid_at: string | null;
  bakong_mock: boolean;
  deeplink_url: string;
}

export interface OrderSummaryDto {
  id: number;
  number: string;
  status: OrderStatus;
  status_label: string;
  status_color: 'warning' | 'success' | 'info' | 'primary' | 'danger' | 'gray';
  currency: Currency;
  exchange_rate: number;
  subtotal_cents: number;
  formatted_subtotal: string;
  discount_cents: number;
  formatted_discount: string;
  discount_code: string | null;
  shipping_fee_cents: number;
  formatted_shipping: string;
  shipping_method: ShippingMethod;
  total_cents: number;
  formatted_total: string;
  total_charged_currency_amount: number;
  formatted_charged_total: string;
  equivalent_khr_amount: number;
  formatted_equivalent_khr: string;
  equivalent_usd_amount: number;
  formatted_equivalent_usd: string;
  items_count: number;
  preview_item_names: string[];
  can_pay_now: boolean;
  created_at: string;
  formatted_created_at: string;
  paid_at: string | null;
}

export interface OrderDetailDto extends OrderSummaryDto {
  email: string;
  phone: string;
  shipping_address: OrderShippingAddressDto;
  billing_address: OrderShippingAddressDto;
  tracking_number: string | null;
  shipping_carrier: string | null;
  customer_notes: string | null;
  shipped_at: string | null;
  delivered_at: string | null;
  cancelled_at: string | null;
  items: OrderItemDto[];
  latest_payment: PaymentDto | null;
  status_timeline: OrderStatusStepDto[];
}

export interface InvoiceStoreInfoDto {
  name: string;
  tagline: string;
  address: string;
  phone: string;
  email: string;
}

export interface TaxInvoiceDto {
  invoice_number: string;
  issued_date: string;
  payment_method_label: string;
  store_info: InvoiceStoreInfoDto;
  order: OrderDetailDto;
  payment: PaymentDto | null;
}

// ============================================================================
// 5. BOOTSTRAP, STATIC PAGES & SIZE GUIDE DTOs
// ============================================================================

export interface SizeGuideRowDto {
  size: string;
  chest_cm: number;
  length_cm: number;
  shoulder_cm: number;
  sleeve_cm: number;
}

export interface SizeGuideTableDto {
  id: 'tees' | 'hoodies';
  title: string;
  columns: {
    size: string;
    chest: string;
    length: string;
    shoulder: string;
    sleeve: string;
  };
  rows: SizeGuideRowDto[];
}

export interface SizeGuideMeasureInstructionDto {
  key: 'chest' | 'length' | 'shoulder' | 'sleeve';
  label: string;
  description: string;
}

export interface SizeGuideDataDto {
  title: string;
  subtitle: string;
  fit_note_title: string;
  fit_note_body: string;
  tip: string;
  tables: SizeGuideTableDto[];
  how_to_measure_title: string;
  how_to_measure: SizeGuideMeasureInstructionDto[];
}

export interface PageSummaryDto {
  slug: InfoPageSlug;
  title: string;
  subtitle: string | null;
  endpoint: string;
}

export interface StructuredPageSectionDto {
  id: string;
  eyebrow?: string | null;
  title: string;
  paragraphs?: string[];
  bullets?: string[];
  items?: Array<{
    question?: string;
    answer?: string;
    label?: string;
    title?: string;
    description?: string;
  }>;
}

export interface StructuredPageTableDto {
  title: string;
  headers: string[];
  rows: string[][];
}

export interface StructuredInfoPageDto {
  slug: InfoPageSlug;
  locale: Locale;
  badge: string | null;
  title: string;
  subtitle: string | null;
  updated_at: string | null;
  sections: StructuredPageSectionDto[];
  tables: StructuredPageTableDto[];
  size_guide: SizeGuideDataDto | null;
  studio_info: {
    studio_title: string;
    street: string;
    city_country: string;
    hours: string;
    telegram_handle: string;
    telegram_url: string;
    email: string;
    phone: string;
  } | null;
}

export interface StoreBootstrapSettingsDto {
  store_name: string;
  store_tagline: string;
  store_tagline_km: string;
  store_email: string;
  store_phone: string;
  store_address: string;
  announcement_banner_en: string;
  announcement_banner_km: string;
  announcement_banner: string;
  exchange_rate: number;
  free_shipping_threshold: number;
  formatted_free_shipping_threshold: string;
  express_shipping_fee_khr: number;
  express_shipping_province: 'Phnom Penh';
  province_shipping_fees: Record<string, number>;
  provinces: string[];
  bakong_mock: boolean;
  bakong_currency: Currency;
  qr_ttl_seconds: number;
}

// ============================================================================
// 6. ENDPOINT REQUEST & RESPONSE CONTRACTS
// ============================================================================

// --- 6.1 Bootstrap & Home ---

export interface BootstrapResponse {
  locale: Locale;
  currency: Currency;
  settings: StoreBootstrapSettingsDto;
  categories: CategoryDto[];
  size_guide: SizeGuideDataDto;
  pages: PageSummaryDto[];
  authenticated: boolean;
  user: UserDto | null;
  cart: CartPayloadDto | null;
}

export interface HomeResponse {
  categories: CategoryDto[];
  featured_products: ProductCardDto[];
  new_arrivals: ProductCardDto[];
  best_sellers: ProductCardDto[];
}

// --- 6.2 Catalog & Predictive Search ---

export interface ProductsQueryParams {
  q?: string;
  category?: string;
  /** Supports single size string or array (`size[]=S&size[]=M`) */
  size?: string | string[];
  color?: string;
  /** Interpreted in active X-Currency (`KHR` integer or `USD` float) */
  min_price?: number;
  /** Interpreted in active X-Currency (`KHR` integer or `USD` float) */
  max_price?: number;
  in_stock?: boolean | 0 | 1;
  sort?: ProductSort;
  page?: number;
  per_page?: number;
}

export interface ShopCategoryFacetDto {
  id: number;
  name: string;
  slug: string;
  products_count: number;
}

export interface ShopColorFacetDto {
  color: string;
  color_code: string;
}

export interface ShopFacetsDto {
  all_products_count: number;
  categories: ShopCategoryFacetDto[];
  available_sizes: string[];
  available_colors: ShopColorFacetDto[];
  active_filter_count: number;
  currency: Currency;
  exchange_rate: number;
}

export interface ProductsResponse extends PaginatedResponse<ProductCardDto> {
  facets: ShopFacetsDto;
}

export interface SearchSuggestQueryParams {
  q?: string;
}

export interface SearchCategorySuggestionDto {
  name: string;
  slug: string;
}

export interface SearchProductSuggestionDto {
  id: number;
  name: string;
  slug: string;
  image_url: string;
  price: string;
  compare_at_price: string | null;
  price_cents: number;
  compare_at_price_cents: number | null;
  category: string | null;
  is_in_stock: boolean;
}

export interface SearchSuggestResponse {
  query: string;
  popular_searches: string[];
  categories: SearchCategorySuggestionDto[];
  products: SearchProductSuggestionDto[];
  total: number;
}

// --- 6.3 Product Detail & Reviews ---

export interface ProductDetailResponse {
  product: ProductDetailDto;
  has_purchased: boolean;
  in_wishlist: boolean;
  related_products: ProductCardDto[];
}

export interface CreateReviewRequest {
  rating: 1 | 2 | 3 | 4 | 5;
  title?: string | null;
  body: string;
}

export interface CreateReviewResponse {
  message: string;
  review: ReviewDto;
  average_rating: number;
  review_count: number;
}

// --- 6.4 Wishlist ---

export interface WishlistResponse {
  items: ProductCardDto[];
  total: number;
}

export interface ToggleWishlistRequest {
  product_id: number;
}

export interface ToggleWishlistResponse {
  added: boolean;
  product_id: number;
  message: string;
  total_wishlists: number;
}

// --- 6.5 Cart & Discounts ---

export interface CartQueryParams {
  province?: string;
  shipping_method?: ShippingMethod;
}

export interface AddCartItemRequest {
  product_variant_id: number;
  quantity: number;
  province?: string;
  shipping_method?: ShippingMethod;
}

export interface UpdateCartItemRequest {
  quantity: number; // 0 deletes item, max 20
  province?: string;
  shipping_method?: ShippingMethod;
}

export interface ApplyDiscountRequest {
  code: string;
  province?: string;
  shipping_method?: ShippingMethod;
}

export interface CartResponse {
  cart: CartPayloadDto;
}

export interface CartMutationResponse {
  message: string;
  cart: CartPayloadDto;
}

// --- 6.6 Checkout, Payment Polling & Tax Invoice ---

export interface CheckoutRequest {
  name?: string | null;
  first_name?: string | null;
  last_name?: string | null;
  email: string;
  phone: string;
  province: string;
  district?: string | null;
  commune?: string | null;
  street_address: string;
  shipping_method: ShippingMethod;
  notes?: string | null;
}

export interface CheckoutResponse {
  message: string;
  order: OrderDetailDto;
  payment: PaymentDto;
  /** Stateless HMAC-signed token for subsequent guest payment/invoice access via X-Order-Token */
  order_access_token: string;
}

export interface OrderPaymentResponse {
  order: OrderDetailDto;
  payment: PaymentDto;
}

export interface PaymentStatusResponse {
  payment_id: number;
  order_number: string;
  order_status: OrderStatus;
  status: PaymentStatus;
  time_remaining_seconds: number;
  paid_at: string | null;
  message: string | null;
}

export interface RegenerateQrResponse {
  message: string;
  order: OrderDetailDto;
  payment: PaymentDto;
}

export interface SimulatePayResponse {
  success: true;
  message: string;
  order: OrderDetailDto;
  payment: PaymentDto;
}

export interface OrderInvoiceResponse {
  invoice: TaxInvoiceDto;
}

// --- 6.7 Authentication ---

export interface RegisterRequest {
  name: string;
  email: string;
  phone: string;
  password: string;
  password_confirmation: string;
  device_name?: string;
}

export interface LoginRequest {
  email: string;
  password: string;
  device_name?: string;
}

export interface AuthResponse {
  token: string;
  token_type: 'Bearer';
  expires_at: string | null;
  user: UserDto;
  cart: CartPayloadDto;
}

export interface LogoutResponse {
  message: string;
}

export interface ForgotPasswordRequest {
  email: string;
}

export interface ForgotPasswordResponse {
  message: string;
  status: string;
}

export interface AuthMeResponse {
  user: UserDto;
}

export interface UpdatePasswordRequest {
  current_password: string;
  password: string;
  password_confirmation: string;
}

export interface UpdatePasswordResponse {
  message: string;
}

// --- 6.8 Customer Account Portal ---

export interface AccountProfileResponse {
  user: UserDto;
  default_shipping_address: AddressDto | null;
}

export interface UpdateProfileRequest {
  name: string;
  phone?: string | null;
}

export interface UpdateProfileResponse {
  message: string;
  user: UserDto;
}

export interface AccountOrdersQueryParams {
  page?: number;
  per_page?: number;
}

export type AccountOrdersResponse = PaginatedResponse<OrderSummaryDto>;

export interface AccountOrderDetailResponse {
  order: OrderDetailDto;
  order_access_token?: string;
}

export interface AccountAddressesResponse {
  addresses: AddressDto[];
  provinces: string[];
}

export interface UpsertAddressRequest {
  name?: string | null;
  first_name?: string | null;
  last_name?: string | null;
  phone: string;
  province: string;
  district?: string | null;
  commune?: string | null;
  street_address: string;
  postal_code?: string | null;
  is_default?: boolean;
}

export interface AccountAddressMutationResponse {
  message: string;
  address: AddressDto;
}

export interface DeleteAddressResponse {
  message: string;
}

// --- 6.9 Info Pages, Contact & Newsletter ---

export type InfoPageResponse = StructuredInfoPageDto;

export interface ContactRequest {
  name: string;
  email: string;
  subject?: string | null;
  message: string;
}

export interface ContactResponse {
  message: string;
  contact_message_id: number;
}

export interface NewsletterRequest {
  email: string;
}

export interface NewsletterResponse {
  message: string;
  email: string;
  is_active: boolean;
}

// ============================================================================
// 7. TYPED ENDPOINT REGISTRY (OPENAPI & CLIENT SDK MATRIX)
// ============================================================================

export type AuthRequirement =
  | 'public'
  | 'optional_bearer'
  | 'required_bearer'
  | 'bearer_or_cart_token'
  | 'bearer_or_order_token';

export interface EndpointSpec<TQuery, TBody, TResponse> {
  method: 'GET' | 'POST' | 'PATCH' | 'PUT' | 'DELETE';
  path: string;
  auth: AuthRequirement;
  successStatus: HttpStatusCode;
  queryType?: TQuery;
  bodyType?: TBody;
  responseType?: TResponse;
}

export interface ResetApiContractRegistry {
  bootstrap: EndpointSpec<undefined, undefined, BootstrapResponse>;
  home: EndpointSpec<undefined, undefined, HomeResponse>;
  products: EndpointSpec<ProductsQueryParams, undefined, ProductsResponse>;
  searchSuggest: EndpointSpec<SearchSuggestQueryParams, undefined, SearchSuggestResponse>;
  productDetail: EndpointSpec<undefined, undefined, ProductDetailResponse>;
  createReview: EndpointSpec<undefined, CreateReviewRequest, CreateReviewResponse>;
  wishlist: EndpointSpec<undefined, undefined, WishlistResponse>;
  toggleWishlist: EndpointSpec<undefined, ToggleWishlistRequest, ToggleWishlistResponse>;
  getCart: EndpointSpec<CartQueryParams, undefined, CartResponse>;
  addCartItem: EndpointSpec<undefined, AddCartItemRequest, CartMutationResponse>;
  updateCartItem: EndpointSpec<undefined, UpdateCartItemRequest, CartMutationResponse>;
  removeCartItem: EndpointSpec<CartQueryParams, undefined, CartMutationResponse>;
  applyDiscount: EndpointSpec<undefined, ApplyDiscountRequest, CartMutationResponse>;
  removeDiscount: EndpointSpec<CartQueryParams, undefined, CartMutationResponse>;
  checkout: EndpointSpec<undefined, CheckoutRequest, CheckoutResponse>;
  getOrderPayment: EndpointSpec<undefined, undefined, OrderPaymentResponse>;
  getPaymentStatus: EndpointSpec<undefined, undefined, PaymentStatusResponse>;
  regenerateQr: EndpointSpec<undefined, undefined, RegenerateQrResponse>;
  simulatePay: EndpointSpec<undefined, undefined, SimulatePayResponse>;
  getOrderInvoice: EndpointSpec<undefined, undefined, OrderInvoiceResponse>;
  register: EndpointSpec<undefined, RegisterRequest, AuthResponse>;
  login: EndpointSpec<undefined, LoginRequest, AuthResponse>;
  logout: EndpointSpec<undefined, undefined, LogoutResponse>;
  forgotPassword: EndpointSpec<undefined, ForgotPasswordRequest, ForgotPasswordResponse>;
  me: EndpointSpec<undefined, undefined, AuthMeResponse>;
  updatePassword: EndpointSpec<undefined, UpdatePasswordRequest, UpdatePasswordResponse>;
  getProfile: EndpointSpec<undefined, undefined, AccountProfileResponse>;
  updateProfile: EndpointSpec<undefined, UpdateProfileRequest, UpdateProfileResponse>;
  getOrders: EndpointSpec<AccountOrdersQueryParams, undefined, AccountOrdersResponse>;
  getOrderDetail: EndpointSpec<undefined, undefined, AccountOrderDetailResponse>;
  getAddresses: EndpointSpec<undefined, undefined, AccountAddressesResponse>;
  createAddress: EndpointSpec<undefined, UpsertAddressRequest, AccountAddressMutationResponse>;
  updateAddress: EndpointSpec<undefined, UpsertAddressRequest, AccountAddressMutationResponse>;
  deleteAddress: EndpointSpec<undefined, undefined, DeleteAddressResponse>;
  getPage: EndpointSpec<undefined, undefined, InfoPageResponse>;
  submitContact: EndpointSpec<undefined, ContactRequest, ContactResponse>;
  subscribeNewsletter: EndpointSpec<undefined, NewsletterRequest, NewsletterResponse>;
}
