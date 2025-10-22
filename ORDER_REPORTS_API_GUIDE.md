# 📋 API Báo cáo Đơn hàng - Request & Response Guide

## 🔐 Authentication Header
```javascript
headers: {
  'Authorization': `Bearer ${token}`,
  'Content-Type': 'application/json'
}
```

---

## 1️⃣ BÁO CÁO ĐƠN HÀNG THEO THỜI GIAN

### 📊 View Report
```javascript
// Request
const response = await fetch('/api/v1/reports/orders?start_date=2025-01-01&end_date=2025-01-31&group_by=day', {
  method: 'GET',
  headers: {
    'Authorization': `Bearer ${token}`,
  }
});
const data = await response.json();
```

#### Query Parameters
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| start_date | string | Đầu tháng hiện tại | Ngày bắt đầu (YYYY-MM-DD) |
| end_date | string | Cuối tháng hiện tại | Ngày kết thúc (YYYY-MM-DD) |
| group_by | string | day | Nhóm theo: day, week, month, year |
| working_shift_id | integer | null | ID ca làm việc (optional) |
| user_id | integer | null | ID user (cho quyền xem riêng) |

#### Response Structure
```json
{
  "status": 200,
  "message": "Lấy báo cáo đơn hàng thành công",
  "data": {
    "summary": {
      "total_orders": 150,
      "total_amount": 125000000,
      "total_discount": 8500000,
      "total_tax": 1250000,
      "total_other_fee": 500000,
      "net_revenue": 115000000,
      "total_return": 5000000,
      "total_capital_price": 75000000,
      "total_profit": 40000000,
      "profit_margin_percent": 34.78
    },
    "period": {
      "start_date": "2025-01-01",
      "end_date": "2025-01-31",
      "group_by": "day"
    },
    "report_items": [
      {
        "key": "2025-01-01",
        "total_order": 15,
        "total": 4500000,
        "total_discount": 300000,
        "total_tax": 45000,
        "total_other_fee": 15000,
        "net_revenue": 4155000,
        "total_return": 0,
        "total_capital_price": 2700000,
        "total_profit": 1455000,
        "total_amount": 4155000
      },
      {
        "key": "2025-01-02",
        "total_order": 12,
        "total": 3800000,
        "total_discount": 250000,
        "total_tax": 38000,
        "total_other_fee": 12000,
        "net_revenue": 3512000,
        "total_return": 0,
        "total_capital_price": 2280000,
        "total_profit": 1232000,
        "total_amount": 3512000
      }
    ]
  }
}
```

### 📥 Export Excel
```javascript
// Request
const response = await fetch('/api/v1/reports/orders/export?start_date=2025-01-01&end_date=2025-01-31&group_by=day', {
  method: 'GET',
  headers: {
    'Authorization': `Bearer ${token}`,
  }
});

// Handle file download
const blob = await response.blob();
const url = URL.createObjectURL(blob);
const a = document.createElement('a');
a.href = url;
a.download = 'bao_cao_don_hang.xlsx';
a.click();
URL.revokeObjectURL(url);
```

---

## 2️⃣ BÁO CÁO CHI TIẾT ĐƠN HÀNG

### 📊 View Detail Report
```javascript
// Request
const response = await fetch('/api/v1/reports/orders/detail?start_date=2025-01-01&end_date=2025-01-31&order_status=4&limit=100', {
  method: 'GET',
  headers: {
    'Authorization': `Bearer ${token}`,
  }
});
const data = await response.json();
```

#### Query Parameters
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| start_date | string | Đầu tháng hiện tại | Ngày bắt đầu |
| end_date | string | Cuối tháng hiện tại | Ngày kết thúc |
| order_status | integer | null | 4: hoàn thành, 5: trả hàng |
| limit | integer | 100 | Số lượng đơn hàng |

#### Response Structure
```json
{
  "status": 200,
  "message": "Lấy báo cáo chi tiết đơn hàng thành công",
  "data": {
    "summary": {
      "total_orders": 150,
      "total_amount": 125000000,
      "total_discount": 8500000,
      "total_profit": 40000000,
      "average_order_value": 833333.33,
      "complete_orders": 145,
      "return_orders": 5
    },
    "orders": [
      {
        "id": 123,
        "order_code": "ORD-123",
        "customer_name": "Nguyễn Văn A",
        "customer_phone": "0901234567",
        "order_date": "2025-01-15 14:30:00",
        "status": "Hoàn thành",
        "status_id": 4,
        "total_amount": 450000,
        "discount_total": 30000,
        "tax_total": 4500,
        "other_fee_total": 1500,
        "capital_price": 270000,
        "profit": 144000,
        "profit_margin": 32.00,
        "items_count": 3
      },
      {
        "id": 124,
        "order_code": "ORD-124",
        "customer_name": "Trần Thị B",
        "customer_phone": "0907654321",
        "order_date": "2025-01-15 16:45:00",
        "status": "Hoàn thành",
        "status_id": 4,
        "total_amount": 380000,
        "discount_total": 25000,
        "tax_total": 3800,
        "other_fee_total": 1200,
        "capital_price": 228000,
        "profit": 123000,
        "profit_margin": 32.37,
        "items_count": 2
      }
    ]
  }
}
```

---

## 🎨 Frontend Component Examples

### React Component Example
```jsx
import React, { useState, useEffect } from 'react';

const OrderReportsPage = () => {
  const [token, setToken] = useState(localStorage.getItem('token'));
  const [loading, setLoading] = useState(false);
  const [orderData, setOrderData] = useState(null);
  const [detailData, setDetailData] = useState(null);
  const [filters, setFilters] = useState({
    start_date: new Date().toISOString().split('T')[0],
    end_date: new Date().toISOString().split('T')[0],
    group_by: 'day',
    order_status: null,
    limit: 100
  });

  // Fetch order report
  const fetchOrderReport = async () => {
    setLoading(true);
    try {
      const queryString = new URLSearchParams(filters).toString();
      const response = await fetch(`/api/v1/reports/orders?${queryString}`, {
        headers: { 'Authorization': `Bearer ${token}` }
      });
      
      if (!response.ok) throw new Error('Failed to fetch');
      
      const result = await response.json();
      setOrderData(result.data);
    } catch (error) {
      console.error('Error:', error);
    } finally {
      setLoading(false);
    }
  };

  // Fetch detail report
  const fetchDetailReport = async () => {
    setLoading(true);
    try {
      const queryString = new URLSearchParams(filters).toString();
      const response = await fetch(`/api/v1/reports/orders/detail?${queryString}`, {
        headers: { 'Authorization': `Bearer ${token}` }
      });
      
      if (!response.ok) throw new Error('Failed to fetch');
      
      const result = await response.json();
      setDetailData(result.data);
    } catch (error) {
      console.error('Error:', error);
    } finally {
      setLoading(false);
    }
  };

  // Export order report
  const exportOrderReport = async () => {
    try {
      const queryString = new URLSearchParams(filters).toString();
      const response = await fetch(`/api/v1/reports/orders/export?${queryString}`, {
        headers: { 'Authorization': `Bearer ${token}` }
      });
      
      if (!response.ok) throw new Error('Export failed');
      
      const blob = await response.blob();
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = 'bao_cao_don_hang.xlsx';
      a.click();
      URL.revokeObjectURL(url);
    } catch (error) {
      console.error('Export error:', error);
    }
  };

  return (
    <div className="order-reports-page">
      <h1>Báo cáo Đơn hàng</h1>
      
      {/* Filters */}
      <div className="filters">
        <input
          type="date"
          value={filters.start_date}
          onChange={(e) => setFilters({...filters, start_date: e.target.value})}
        />
        <input
          type="date"
          value={filters.end_date}
          onChange={(e) => setFilters({...filters, end_date: e.target.value})}
        />
        <select
          value={filters.group_by}
          onChange={(e) => setFilters({...filters, group_by: e.target.value})}
        >
          <option value="day">Theo ngày</option>
          <option value="week">Theo tuần</option>
          <option value="month">Theo tháng</option>
          <option value="year">Theo năm</option>
        </select>
        <button onClick={fetchOrderReport}>Xem báo cáo</button>
        <button onClick={exportOrderReport}>Export Excel</button>
      </div>

      {/* Summary Cards */}
      {orderData?.summary && (
        <div className="summary-cards">
          <div className="card">
            <h3>Tổng đơn hàng</h3>
            <p>{orderData.summary.total_orders}</p>
          </div>
          <div className="card">
            <h3>Tổng doanh thu</h3>
            <p>{orderData.summary.total_amount?.toLocaleString()} VNĐ</p>
          </div>
          <div className="card">
            <h3>Tổng lợi nhuận</h3>
            <p>{orderData.summary.total_profit?.toLocaleString()} VNĐ</p>
          </div>
          <div className="card">
            <h3>Tỷ suất LN</h3>
            <p>{orderData.summary.profit_margin_percent?.toFixed(2)}%</p>
          </div>
        </div>
      )}

      {/* Chart */}
      {orderData?.report_items && (
        <div className="chart-section">
          <h2>Biểu đồ doanh thu theo thời gian</h2>
          {/* Implement your chart component here */}
          <div className="chart-placeholder">
            {/* Chart data: orderData.report_items */}
          </div>
        </div>
      )}

      {/* Detail Report */}
      <div className="detail-section">
        <h2>Chi tiết đơn hàng</h2>
        <button onClick={fetchDetailReport}>Xem chi tiết</button>
        
        {detailData?.orders && (
          <div className="orders-table">
            <table>
              <thead>
                <tr>
                  <th>Mã đơn</th>
                  <th>Khách hàng</th>
                  <th>Ngày</th>
                  <th>Trạng thái</th>
                  <th>Tổng tiền</th>
                  <th>Lợi nhuận</th>
                  <th>Tỷ suất LN</th>
                </tr>
              </thead>
              <tbody>
                {detailData.orders.map(order => (
                  <tr key={order.id}>
                    <td>{order.order_code}</td>
                    <td>{order.customer_name}</td>
                    <td>{order.order_date}</td>
                    <td>
                      <span className={`status status-${order.status_id}`}>
                        {order.status}
                      </span>
                    </td>
                    <td>{order.total_amount?.toLocaleString()} VNĐ</td>
                    <td>{order.profit?.toLocaleString()} VNĐ</td>
                    <td>{order.profit_margin?.toFixed(2)}%</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>

      {loading && <div className="loading">Đang tải...</div>}
    </div>
  );
};

export default OrderReportsPage;
```

---

## 📊 Data Types & Validation

### Query Parameters Types
```typescript
interface OrderReportParams {
  start_date?: string;           // YYYY-MM-DD, default: start of month
  end_date?: string;             // YYYY-MM-DD, default: end of month
  group_by?: 'day' | 'week' | 'month' | 'year';  // default: 'day'
  working_shift_id?: number;     // optional
  user_id?: number;              // optional, for self-view permission
}

interface OrderDetailParams {
  start_date?: string;           // default: start of month
  end_date?: string;             // default: end of month
  order_status?: number;         // 4: complete, 5: return
  limit?: number;                // default: 100
}
```

### Response Types
```typescript
interface OrderReportData {
  summary: {
    total_orders: number;
    total_amount: number;
    total_discount: number;
    total_tax: number;
    total_other_fee: number;
    net_revenue: number;
    total_return: number;
    total_capital_price: number;
    total_profit: number;
    profit_margin_percent: number;
  };
  period: {
    start_date: string;
    end_date: string;
    group_by: string;
  };
  report_items: OrderReportItem[];
}

interface OrderReportItem {
  key: string;                   // Date key (2025-01-01, 2025-W03, etc.)
  total_order: number;
  total: number;
  total_discount: number;
  total_tax: number;
  total_other_fee: number;
  net_revenue: number;
  total_return: number;
  total_capital_price: number;
  total_profit: number;
  total_amount: number;
}

interface OrderDetailData {
  summary: {
    total_orders: number;
    total_amount: number;
    total_discount: number;
    total_profit: number;
    average_order_value: number;
    complete_orders: number;
    return_orders: number;
  };
  orders: OrderDetail[];
}

interface OrderDetail {
  id: number;
  order_code: string;
  customer_name: string;
  customer_phone: string;
  order_date: string;
  status: string;
  status_id: number;
  total_amount: number;
  discount_total: number;
  tax_total: number;
  other_fee_total: number;
  capital_price: number;
  profit: number;
  profit_margin: number;
  items_count: number;
}
```

---

## 🎯 Key Features

### 1. **Grouping Options**
- **day**: Theo ngày (2025-01-01, 2025-01-02, ...)
- **week**: Theo tuần (2025-W03, 2025-W04, ...)
- **month**: Theo tháng (2025-01, 2025-02, ...)
- **year**: Theo năm (2025, 2026, ...)

### 2. **Order Status Handling**
- **Complete Orders (status_id = 4)**: Tính vào doanh thu, lợi nhuận
- **Return Orders (status_id = 5)**: Trừ khỏi doanh thu, tính vào tổng trả hàng

### 3. **Financial Calculations**
```javascript
// Order totals calculation
const discount = order.discount_total + order.promotion_total;
const total = order.final_price - order.tax_total - order.other_fee_total + discount;
const profit = total - discount - capital_price;

// Net revenue
const netRevenue = totalAmount - totalReturn;

// Profit margin
const profitMargin = (totalProfit / netRevenue) * 100;
```

### 4. **Permission Handling**
- **Admin**: Xem tất cả đơn hàng
- **Self-view**: Chỉ xem đơn hàng của mình (user_id filter)

---

## 🚀 Use Cases

### 1. **Daily Sales Report**
```javascript
// Xem doanh thu theo ngày
const dailyReport = await fetchOrderReport({
  start_date: '2025-01-01',
  end_date: '2025-01-31',
  group_by: 'day'
});
```

### 2. **Monthly Summary**
```javascript
// Xem tổng kết theo tháng
const monthlyReport = await fetchOrderReport({
  start_date: '2025-01-01',
  end_date: '2025-01-31',
  group_by: 'month'
});
```

### 3. **Order Analysis**
```javascript
// Phân tích chi tiết đơn hàng
const detailReport = await fetchDetailReport({
  start_date: '2025-01-01',
  end_date: '2025-01-31',
  order_status: 4,  // Chỉ đơn hoàn thành
  limit: 200
});
```

### 4. **Export for Accounting**
```javascript
// Export báo cáo cho kế toán
const exportReport = await exportOrderReport({
  start_date: '2025-01-01',
  end_date: '2025-01-31',
  group_by: 'day'
});
```

---

## 📈 Charts & Visualizations

### 1. **Revenue Trend Chart**
```javascript
// Data for chart
const chartData = orderData.report_items.map(item => ({
  x: item.key,           // Date
  y: item.net_revenue    // Revenue amount
}));
```

### 2. **Profit Margin Chart**
```javascript
// Profit margin over time
const profitData = orderData.report_items.map(item => ({
  x: item.key,
  y: (item.total_profit / item.net_revenue) * 100
}));
```

### 3. **Order Count Chart**
```javascript
// Number of orders per period
const orderCountData = orderData.report_items.map(item => ({
  x: item.key,
  y: item.total_order
}));
```

---

## 🔧 Error Handling

### Error Response Format
```json
{
  "status": 500,
  "message": "Lỗi khi lấy báo cáo đơn hàng",
  "error": "Error details..."
}
```

### Frontend Error Handling
```javascript
const fetchOrderReport = async (params) => {
  try {
    const response = await fetch(url, {
      headers: { 'Authorization': `Bearer ${token}` }
    });
    
    if (!response.ok) {
      const error = await response.json();
      throw new Error(error.message || 'Request failed');
    }
    
    const data = await response.json();
    return data;
  } catch (error) {
    console.error('API Error:', error);
    // Show error message to user
    alert(`Lỗi: ${error.message}`);
    throw error;
  }
};
```

---

## 🎨 UI Components Suggestions

### 1. **Summary Dashboard**
- Cards: Tổng đơn, Doanh thu, Lợi nhuận, Tỷ suất LN
- Charts: Line chart doanh thu theo thời gian
- Filters: Date picker, group by selector

### 2. **Order Detail Table**
- Sortable columns
- Status badges (Complete/Return)
- Profit margin indicators
- Export functionality

### 3. **Charts Section**
- Revenue trend line chart
- Profit margin bar chart
- Order count area chart
- Interactive tooltips

---

## ✅ Ready to Code!

Với data structure này, bạn có thể:
1. ✅ Tạo dashboard báo cáo đơn hàng
2. ✅ Implement charts và visualizations
3. ✅ Build order detail tables
4. ✅ Add filters và date pickers
5. ✅ Export Excel functionality
6. ✅ Handle permissions và user roles

**Happy Coding! 🎉**
