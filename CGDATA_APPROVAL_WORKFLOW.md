# CGData Approval Workflow

## Overview

The Category Generate Data (CGData) functionality has been updated to implement an approval workflow instead of immediately creating products. This ensures data quality and gives administrators control over what products are created in the system.

## New Workflow

### Before (Old Workflow)
1. Admin uses CGData to fetch products from external API
2. Admin reviews the fetched data
3. Admin clicks "Save All Products" button
4. Products are immediately created in the system

### After (New Workflow)
1. Admin uses CGData to fetch products from external API
2. Admin reviews the fetched data
3. Admin clicks "Save to Approval Queue" button
4. Products are saved to temporary `product_cgd` table with status "pending"
5. Admin reviews and approves/rejects each product individually
6. Approved products can be converted to actual Product entities

## Database Changes

### New Table: `product_cgd`

The new table stores temporary product data with the following structure:

- **Basic Info**: name, description, SKU, price, URL
- **Relations**: category_id (integer), brand_id (integer), product_id (integer)
- **Status Management**: status (pending/approved/rejected/processing)
- **Metadata**: images (JSON), technical_specs (JSON), metadata (JSON)
- **Audit Trail**: created_by, approved_by, approved_at, rejection_reason, created, modified
- **Batch Management**: batch_id for grouping related entries
- **Product Reference**: product_id (nullable) - links to created Product entity
- **External Data**: original API response data

## New Routes

### ProductCGD Management
- `/admin/product-cgd/` - Main approval interface
- `/admin/product-cgd/{id}/show` - View individual entry details
- `/admin/product-cgd/{id}/approve` - Approve individual entry
- `/admin/product-cgd/{id}/reject` - Reject individual entry
- `/admin/product-cgd/bulk-approve` - Bulk approve multiple entries
- `/admin/product-cgd/bulk-reject` - Bulk reject multiple entries
- `/admin/product-cgd/convert-approved` - Convert approved entries to products

### Updated CGData Route
- `/admin/product/category/{id}/cgdata/save-products` - Now saves to approval queue instead of creating products

## Admin Interface

### Main Approval Page (`/admin/product-cgd/`)
- **Statistics Cards**: Shows counts for pending, approved, rejected, and total entries
- **Status Tabs**: Filter by status (pending, approved, rejected)
- **Search Functionality**: Search by name, SKU, or description
- **Product ID Column**: Shows Product ID if entry has been converted, with link to Product
- **Bulk Actions**: Select multiple entries for approval/rejection
- **Pagination**: Handle large numbers of entries efficiently

### Individual Entry View (`/admin/product-cgd/{id}/show`)
- **Product Information**: Display all collected data
- **Technical Specifications**: Show technical specs in organized table
- **Images**: Display product images (if available)
- **Metadata**: Show additional metadata fields
- **Product Reference**: Shows Product ID if entry has been converted to a product
- **System Information**: Creation date, creator, approval status
- **Actions**: Approve, reject, or view details

## Benefits

### Data Quality Control
- Administrators can review all data before product creation
- Rejection reasons can be documented
- Prevents low-quality or duplicate products from entering the system

### Scalability
- Handles large numbers of products efficiently
- Batch processing capabilities
- No timeout issues during bulk operations

### Audit Trail
- Complete tracking of who created, approved, or rejected entries
- Timestamps for all actions
- Rejection reasons for future reference

### Future Extensibility
- Framework for global content approval system
- Can be extended to other content types
- Supports different approval workflows

### Product ID Tracking
- **Initial State**: `product_id` is `null` when ProductCGD entry is created
- **After Approval**: `product_id` remains `null` until conversion
- **During Conversion**: Status changes to "processing" and `product_id` is set
- **After Conversion**: `product_id` contains the ID of the created Product entity
- **Benefits**: 
  - Complete traceability from approval to final product
  - Direct navigation from ProductCGD to created Product
  - Audit trail for product creation process

## Usage Instructions

### For CGData Users
1. Use CGData as usual to fetch products from external API
2. Review the fetched data in the modal
3. Click "Save to Approval Queue" instead of "Save All Products"
4. You'll be redirected to the approval interface

### For Administrators
1. Navigate to Products Catalog → CGData Approval
2. Review pending entries
3. Approve or reject individual entries with reasons
4. Use bulk actions for efficiency
5. Convert approved entries to actual products when ready

## Technical Implementation

### New Files Created
- `src/ProductBundle/Entity/ProductCGD.php` - Entity for temporary storage
- `src/ProductBundle/Repository/ProductCGDRepository.php` - Database operations
- `src/ProductBundle/Service/ProductCGDService.php` - Business logic
- `src/ProductBundle/Controller/Administration/ProductCGDController.php` - Controller
- `templates/product/admin/productCGD/index.html.twig` - Main interface
- `templates/product/admin/productCGD/show.html.twig` - Detail view

### Product ID Implementation
- **Entity Field**: `product_id` (nullable integer) in ProductCGD entity
- **Service Logic**: Set during `convertApprovedToProducts()` method
- **Template Display**: Shows as clickable link to Product edit page
- **Status Integration**: Links approval workflow to final product creation

### Modified Files
- `src/ProductBundle/Controller/Administration/CategoryController.php` - Updated CGData save method
- `src/ProductBundle/Controller/Administration/ProductController.php` - Added redirect method
- `src/ProductBundle/Resources/config/routing.yaml` - Added new routes
- `templates/adminTemplate/menu.html.twig` - Added navigation link
- `templates/product/admin/category/_cgdata.html.twig` - Updated button text and description

### Service Injection Fixes
- **CategoryController**: Fixed ProductCGDService injection in `cgdataSaveProducts()` method
- **ProductCGDController**: Fixed ProductCGDService injection in all methods
- **Dependency Injection**: Replaced `container->get()` calls with proper method parameter injection

### Database Migration
- `migrations/Version20250830140953.php` - Creates the `product_cgd` table
- `migrations/Version20250830142422.php` - Adds `product_id` field for Product reference

## Future Enhancements

### Global Approval System
- Extend approval workflow to other content types
- Unified approval interface for all content
- Configurable approval rules and workflows

### Advanced Features
- Email notifications for approval requests
- Approval delegation and escalation
- Integration with external approval systems
- Advanced filtering and reporting

### Performance Optimizations
- Background job processing for large batches
- Caching for frequently accessed data
- Database indexing optimizations

## Notes

- **No Image Processing**: Image handling is currently disabled to simplify the implementation
- **Batch Management**: Each CGData operation gets a unique batch ID for tracking
- **Status Workflow**: pending → approved/rejected → processing (during conversion) → completed
- **Error Handling**: Comprehensive error handling and user feedback
- **Security**: All routes require ROLE_ADMIN permission

## 📚 **Additional Documentation**

- **Implementation Summary**: `CGDATA_IMPLEMENTATION_SUMMARY.md` - Complete technical overview
- **API Usage**: See `README_API_Usage.md` for related API documentation
- **Code Comments**: All new files include detailed inline documentation
