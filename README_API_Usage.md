# Content Recommendation API Usage with Product Studio Intelligence

## Woodenizr Content Recommendation Endpoint
```
POST /admin/product/admin/content-recommendation/generate-recommendations
```

## Using Product Studio Intelligence API

### Basic Usage (Default - Local Product Studio)
```bash
curl -X POST http://127.0.0.1:8000/admin/product/admin/content-recommendation/generate-recommendations \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "batch_size": 25
  }'
```

### With Custom API Endpoint
```bash
curl -X POST http://127.0.0.1:8000/admin/product/admin/content-recommendation/generate-recommendations \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "api_endpoint": "http://localhost:8000/api/products/intelligence",
    "batch_size": 25
  }'
```

### With Product Studio API Key
```bash
curl -X POST http://127.0.0.1:8000/admin/product/admin/content-recommendation/generate-recommendations \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "api_endpoint": "http://your-product-studio-domain.com/api/products/intelligence",
    "api_key": "demo_key_12345",
    "batch_size": 50,
    "offset": 0
  }'
```

### Production Example
```bash
curl -X POST http://127.0.0.1:8000/admin/product/admin/content-recommendation/generate-recommendations \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "api_endpoint": "https://your-production-domain.com/api/products/intelligence",
    "batch_size": 30
  }'
```

## Parameters
- `api_endpoint`: Product Studio Intelligence API URL (`/api/products/intelligence`)
- `api_key` (optional): Your Product Studio API key if authentication is enabled
- `batch_size` (optional): Products per batch (default: 50, recommended: 25-30 for AI processing)
- `offset` (optional): Starting position (default: 0)

## What Woodenizr sends to Product Studio API
For each product, Woodenizr sends data from the database:
```json
{
  "name": "Handcrafted Oak Dining Table",
  "model_number": "HDT-001",
  "brand": "WoodCraft Masters",
  "category": "Dining Furniture"
}
```

**Database Field Mapping:**
- `name` ← `product.title` (fallback: "Unknown Product")
- `model_number` ← `product.sku` (fallback: "N/A")  
- `brand` ← `product.brand.title` (fallback: "Unknown Brand")
- `category` ← `product.category.title` (fallback: "Uncategorized")

## Product Studio Intelligence Response
Product Studio will return comprehensive product data:
```json
{
  "status": "success",
  "data": {
    "success": true,
    "product": {
      "name": "Premium Wooden Dining Table - Handcrafted Excellence",
      "seo_title": "Handcrafted Wooden Dining Table | Premium Furniture",
      "brief": "Premium handcrafted wooden dining table with natural grain finish",
      "description": "Experience the perfect blend of elegance and functionality with our premium wooden dining table...",
      "seo_keywords": ["wooden dining table", "handcrafted furniture", "premium wood"],
      "meta_description": "Shop our premium handcrafted wooden dining table. Perfect for modern homes...",
      "gallery_images": ["https://url1.jpg", "https://url2.jpg"],
      "image_alt_texts": ["Handcrafted wooden dining table", "Premium furniture detail"],
      "specifications": {
        "material": "Premium hardwood",
        "finish": "Natural grain",
        "dimensions": "L72\" x W36\" x H30\""
      }
    },
    "seo_data": {
      "title": "Handcrafted Wooden Dining Table | Premium Furniture",
      "meta_description": "Shop our premium handcrafted wooden dining table...",
      "keywords": ["wooden dining table", "handcrafted furniture"],
      "structured_data": {
        "@context": "https://schema.org/",
        "@type": "Product",
        "name": "Premium Wooden Dining Table"
      }
    }
  }
}
```

## Woodenizr Response
```json
{
  "success": true,
  "message": "Processed 15 products successfully.",
  "processed": 15,
  "errors": [],
  "total": 150,
  "next_offset": 50,
  "has_more": true
}
```

## Processing Flow
1. **Woodenizr** loops through products with titles/SKUs
2. **Sends product data** to Product Studio Intelligence API
3. **Product Studio** processes with AI and returns enriched data
4. **Woodenizr** saves the complete JSON response in `recommended` field
5. **Sets state = 1** (NEW) for manual review
6. **Links to product** via Product relationship

## Benefits of Product Studio Integration
- ✅ **AI-Generated SEO Content**: Optimized titles, descriptions, keywords
- ✅ **Professional Image Discovery**: High-quality product images with alt-text
- ✅ **Structured Data**: Schema.org markup for search engines
- ✅ **Comprehensive Specifications**: Auto-extracted product details
- ✅ **Multi-language Support**: International product optimization
- ✅ **Manufacturer Data**: Scraped from official brand websites

## Notes
- Requires admin authentication on Woodenizr
- Product Studio processes ~2.5 seconds per product
- Recommended batch size: 25-30 for optimal performance
- All Product Studio responses saved as JSON in `recommended` field
- Manual review recommended (state = 1: NEW) before publishing
