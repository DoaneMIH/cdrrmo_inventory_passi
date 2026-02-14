<?php
require_once 'config.php';
check_login();

$page_title = 'Reports';

require_once 'header.php';
?>

<!-- <div style="margin-bottom: 30px;">
    <h2 style="color: var(--primary-blue); margin-bottom: 10px;">
        <i class="fas fa-chart-bar"></i> Inventory Reports
    </h2>
    <p style="color: var(--gray-600);">
        Generate and view various inventory reports and analytics
    </p>
</div> -->

<!-- Report Cards -->
<div class="dashboard-cards">
    <div class="card">
        <div style="padding: 25px;">
            <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 15px;">
                <div style="width: 50px; height: 50px; border-radius: 12px; background: linear-gradient(135deg, var(--secondary-blue), var(--primary-blue)); display: flex; align-items: center; justify-content: center; color: white; font-size: 24px;">
                    <i class="fas fa-boxes"></i>
                </div>
                <div>
                    <h3 style="margin: 0; color: var(--gray-800);">Inventory Summary</h3>
                    <p style="margin: 5px 0 0 0; color: var(--gray-500); font-size: 13px;">
                        Complete inventory status
                    </p>
                </div>
            </div>
            <p style="color: var(--gray-600); font-size: 14px; margin-bottom: 15px;">
                View all inventory items with current stock levels, values, and status.
            </p>
            <a href="report_inventory.php" class="btn btn-primary btn-block">
                <i class="fas fa-file-alt"></i> Generate Report
            </a>
        </div>
    </div>
    
    <div class="card">
        <div style="padding: 25px;">
            <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 15px;">
                <div style="width: 50px; height: 50px; border-radius: 12px; background: linear-gradient(135deg, var(--success), #059669); display: flex; align-items: center; justify-content: center; color: white; font-size: 24px;">
                    <i class="fas fa-exchange-alt"></i>
                </div>
                <div>
                    <h3 style="margin: 0; color: var(--gray-800);">Transaction Report</h3>
                    <p style="margin: 5px 0 0 0; color: var(--gray-500); font-size: 13px;">
                        All inventory movements
                    </p>
                </div>
            </div>
            <p style="color: var(--gray-600); font-size: 14px; margin-bottom: 15px;">
                View all transactions including received and distributed items with date range.
            </p>
            <a href="report_transactions.php" class="btn btn-success btn-block">
                <i class="fas fa-file-alt"></i> Generate Report
            </a>
        </div>
    </div>
    
    <div class="card">
        <div style="padding: 25px;">
            <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 15px;">
                <div style="width: 50px; height: 50px; border-radius: 12px; background: linear-gradient(135deg, var(--warning), #d97706); display: flex; align-items: center; justify-content: center; color: white; font-size: 24px;">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div>
                    <h3 style="margin: 0; color: var(--gray-800);">Low Stock Report</h3>
                    <p style="margin: 5px 0 0 0; color: var(--gray-500); font-size: 13px;">
                        Items needing restock
                    </p>
                </div>
            </div>
            <p style="color: var(--gray-600); font-size: 14px; margin-bottom: 15px;">
                List of items below minimum stock levels that need immediate attention.
            </p>
            <a href="low_stock.php" class="btn btn-warning btn-block">
                <i class="fas fa-file-alt"></i> View Report
            </a>
        </div>
    </div>
    
    <div class="card">
        <div style="padding: 25px;">
            <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 15px;">
                <div style="width: 50px; height: 50px; border-radius: 12px; background: linear-gradient(135deg, var(--primary-yellow), #f59e0b); display: flex; align-items: center; justify-content: center; color: white; font-size: 24px;">
                    <i class="fas fa-tags"></i>
                </div>
                <div>
                    <h3 style="margin: 0; color: var(--gray-800);">Category Report</h3>
                    <p style="margin: 5px 0 0 0; color: var(--gray-500); font-size: 13px;">
                        Items by category
                    </p>
                </div>
            </div>
            <p style="color: var(--gray-600); font-size: 14px; margin-bottom: 15px;">
                View inventory breakdown by categories with stock and value totals.
            </p>
            <a href="report_categories.php" class="btn btn-primary btn-block" style="background: var(--primary-yellow); border-color: var(--primary-yellow);">
                <i class="fas fa-file-alt"></i> Generate Report
            </a>
        </div>
    </div>
    
    <div class="card">
        <div style="padding: 25px;">
            <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 15px;">
                <div style="width: 50px; height: 50px; border-radius: 12px; background: linear-gradient(135deg, var(--danger), #dc2626); display: flex; align-items: center; justify-content: center; color: white; font-size: 24px;">
                    <i class="fas fa-peso-sign"></i>
                </div>
                <div>
                    <h3 style="margin: 0; color: var(--gray-800);">Valuation Report</h3>
                    <p style="margin: 5px 0 0 0; color: var(--gray-500); font-size: 13px;">
                        Inventory value analysis
                    </p>
                </div>
            </div>
            <p style="color: var(--gray-600); font-size: 14px; margin-bottom: 15px;">
                Calculate total inventory value, costs, and financial summaries.
            </p>
            <a href="report_valuation.php" class="btn btn-danger btn-block">
                <i class="fas fa-file-alt"></i> Generate Report
            </a>
        </div>
    </div>
    
    <div class="card">
        <div style="padding: 25px;">
            <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 15px;">
                <div style="width: 50px; height: 50px; border-radius: 12px; background: linear-gradient(135deg, #8b5cf6, #7c3aed); display: flex; align-items: center; justify-content: center; color: white; font-size: 24px;">
                    <i class="fas fa-truck"></i>
                </div>
                <div>
                    <h3 style="margin: 0; color: var(--gray-800);">Supplier Report</h3>
                    <p style="margin: 5px 0 0 0; color: var(--gray-500); font-size: 13px;">
                        Supplier transactions
                    </p>
                </div>
            </div>
            <p style="color: var(--gray-600); font-size: 14px; margin-bottom: 15px;">
                View transaction history and totals for each supplier.
            </p>
            <a href="report_suppliers.php" class="btn btn-primary btn-block" style="background: #8b5cf6; border-color: #8b5cf6;">
                <i class="fas fa-file-alt"></i> Generate Report
            </a>
        </div>
    </div>
</div>

<!-- Quick Statistics -->
<?php
// Get quick stats for display
$stats_query = $conn->query("
    SELECT 
        COUNT(*) as total_items,
        SUM(items_on_hand) as total_stock,
        SUM(items_on_hand * unit_cost) as total_value,
        SUM(CASE WHEN items_on_hand <= minimum_stock_level THEN 1 ELSE 0 END) as low_stock_count
    FROM inventory_items 
    WHERE is_active = 1
");
$quick_stats = $stats_query->fetch_assoc();

$trans_stats = $conn->query("
    SELECT 
        COUNT(*) as total_transactions,
        SUM(CASE WHEN transaction_type = 'received' THEN quantity ELSE 0 END) as total_received,
        SUM(CASE WHEN transaction_type = 'distributed' THEN quantity ELSE 0 END) as total_distributed,
        SUM(total_cost) as total_value
    FROM transactions
    WHERE YEAR(transaction_date) = YEAR(CURDATE())
");
$trans_data = $trans_stats->fetch_assoc();
?>

<div class="card" style="margin-top: 30px;">
    <div style="padding: 30px;">
        <h3 style="margin: 0 0 20px 0; color: var(--gray-800);">
            <i class="fas fa-chart-line"></i> Quick Statistics
        </h3>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
            <div style="background: var(--light-blue); padding: 20px; border-radius: 8px; text-align: center;">
                <div style="font-size: 14px; color: var(--primary-blue); margin-bottom: 5px;">Total Items</div>
                <div style="font-size: 32px; font-weight: 700; color: var(--primary-blue);">
                    <?php echo number_format($quick_stats['total_items']); ?>
                </div>
            </div>
            
            <div style="background: #d1fae5; padding: 20px; border-radius: 8px; text-align: center;">
                <div style="font-size: 14px; color: #065f46; margin-bottom: 5px;">Total Stock Units</div>
                <div style="font-size: 32px; font-weight: 700; color: #065f46;">
                    <?php echo number_format($quick_stats['total_stock']); ?>
                </div>
            </div>
            
            <div style="background: #fef3c7; padding: 20px; border-radius: 8px; text-align: center;">
                <div style="font-size: 14px; color: #92400e; margin-bottom: 5px;">Inventory Value</div>
                <div style="font-size: 32px; font-weight: 700; color: #92400e;">
                    ₱<?php echo number_format($quick_stats['total_value'], 2); ?>
                </div>
            </div>
            
            <div style="background: #fee2e2; padding: 20px; border-radius: 8px; text-align: center;">
                <div style="font-size: 14px; color: #991b1b; margin-bottom: 5px;">Low Stock Items</div>
                <div style="font-size: 32px; font-weight: 700; color: #991b1b;">
                    <?php echo number_format($quick_stats['low_stock_count']); ?>
                </div>
            </div>
            
            <div style="background: var(--gray-100); padding: 20px; border-radius: 8px; text-align: center;">
                <div style="font-size: 14px; color: var(--gray-700); margin-bottom: 5px;">Transactions (YTD)</div>
                <div style="font-size: 32px; font-weight: 700; color: var(--gray-800);">
                    <?php echo number_format($trans_data['total_transactions']); ?>
                </div>
            </div>
            
            <div style="background: #d1fae5; padding: 20px; border-radius: 8px; text-align: center;">
                <div style="font-size: 14px; color: #065f46; margin-bottom: 5px;">Items Received (YTD)</div>
                <div style="font-size: 32px; font-weight: 700; color: #065f46;">
                    <?php echo number_format($trans_data['total_received']); ?>
                </div>
            </div>
            
            <div style="background: #fef3c7; padding: 20px; border-radius: 8px; text-align: center;">
                <div style="font-size: 14px; color: #92400e; margin-bottom: 5px;">Items Distributed (YTD)</div>
                <div style="font-size: 32px; font-weight: 700; color: #92400e;">
                    <?php echo number_format($trans_data['total_distributed']); ?>
                </div>
            </div>
            
            <div style="background: var(--light-blue); padding: 20px; border-radius: 8px; text-align: center;">
                <div style="font-size: 14px; color: var(--primary-blue); margin-bottom: 5px;">Transaction Value (YTD)</div>
                <div style="font-size: 32px; font-weight: 700; color: var(--primary-blue);">
                    ₱<?php echo number_format($trans_data['total_value'], 2); ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>