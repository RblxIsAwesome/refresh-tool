/**
 * Analytics Charts - Chart.js Integration
 * 
 * Renders various charts for the analytics dashboard
 * 
 * @package RobloxRefresher
 * @version 1.0.0
 */

class AnalyticsCharts {
    constructor() {
        this.charts = {};
        this.refreshInterval = null;
    }
    
    /**
     * Initialize all charts
     */
    async init() {
        try {
            const stats = await this.fetchStats();
            
            this.renderLineChart(stats);
            this.renderPieChart(stats);
            this.renderBarChart(stats);
            this.renderDoughnutChart(stats);
            
            // Start auto-refresh
            this.startAutoRefresh();
        } catch (error) {
            console.error('Failed to initialize charts:', error);
        }
    }
    
    /**
     * Fetch statistics from API
     */
    async fetchStats() {
        const response = await fetch('/api/stats.php');
        if (!response.ok) {
            throw new Error('Failed to fetch statistics');
        }
        return await response.json();
    }
    
    /**
     * Render line chart - Refreshes over time (last 7 days)
     */
    renderLineChart(stats) {
        const canvas = document.getElementById('lineChart');
        if (!canvas) return;
        
        const ctx = canvas.getContext('2d');
        
        // Prepare data from daily stats
        const labels = [];
        const successData = [];
        const failedData = [];
        
        // Get last 7 days
        for (let i = 6; i >= 0; i--) {
            const date = new Date();
            date.setDate(date.getDate() - i);
            const dateStr = date.toISOString().split('T')[0];
            
            labels.push(date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }));
            
            const dayData = stats.daily[dateStr] || { success: 0, failed: 0 };
            successData.push(dayData.success);
            failedData.push(dayData.failed);
        }
        
        if (this.charts.line) {
            this.charts.line.destroy();
        }
        
        this.charts.line = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Successful',
                        data: successData,
                        borderColor: '#29c27f',
                        backgroundColor: 'rgba(41, 194, 127, 0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Failed',
                        data: failedData,
                        borderColor: '#f05555',
                        backgroundColor: 'rgba(240, 85, 85, 0.1)',
                        tension: 0.4,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        labels: {
                            color: 'rgba(255, 255, 255, 0.92)',
                            font: { size: 12 }
                        }
                    },
                    title: {
                        display: true,
                        text: 'Refreshes Over Time (Last 7 Days)',
                        color: 'rgba(255, 255, 255, 0.92)',
                        font: { size: 16, weight: 'bold' }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { color: 'rgba(255, 255, 255, 0.62)' },
                        grid: { color: 'rgba(255, 255, 255, 0.06)' }
                    },
                    x: {
                        ticks: { color: 'rgba(255, 255, 255, 0.62)' },
                        grid: { color: 'rgba(255, 255, 255, 0.06)' }
                    }
                }
            }
        });
    }
    
    /**
     * Render pie chart - Success vs Failed refreshes
     */
    renderPieChart(stats) {
        const canvas = document.getElementById('pieChart');
        if (!canvas) return;
        
        const ctx = canvas.getContext('2d');
        
        if (this.charts.pie) {
            this.charts.pie.destroy();
        }
        
        this.charts.pie = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: ['Successful', 'Failed'],
                datasets: [{
                    data: [stats.success || 0, stats.failed || 0],
                    backgroundColor: ['#29c27f', '#f05555'],
                    borderWidth: 2,
                    borderColor: '#070A12'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        labels: {
                            color: 'rgba(255, 255, 255, 0.92)',
                            font: { size: 12 }
                        }
                    },
                    title: {
                        display: true,
                        text: 'Success vs Failed Refreshes',
                        color: 'rgba(255, 255, 255, 0.92)',
                        font: { size: 16, weight: 'bold' }
                    }
                }
            }
        });
    }
    
    /**
     * Render bar chart - Hourly usage patterns (last 24 hours)
     */
    renderBarChart(stats) {
        const canvas = document.getElementById('barChart');
        if (!canvas) return;
        
        const ctx = canvas.getContext('2d');
        
        // Prepare hourly data (last 24 hours)
        const labels = [];
        const data = [];
        
        for (let i = 23; i >= 0; i--) {
            const date = new Date();
            date.setHours(date.getHours() - i);
            const hourStr = date.toISOString().slice(0, 13) + ':00';
            
            labels.push(date.getHours() + ':00');
            
            const hourData = stats.hourly[hourStr] || { total: 0 };
            data.push(hourData.total);
        }
        
        if (this.charts.bar) {
            this.charts.bar.destroy();
        }
        
        this.charts.bar = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Requests',
                    data: data,
                    backgroundColor: 'rgba(124, 182, 255, 0.6)',
                    borderColor: '#7CB6FF',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    title: {
                        display: true,
                        text: 'Hourly Usage Pattern (Last 24 Hours)',
                        color: 'rgba(255, 255, 255, 0.92)',
                        font: { size: 16, weight: 'bold' }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { color: 'rgba(255, 255, 255, 0.62)' },
                        grid: { color: 'rgba(255, 255, 255, 0.06)' }
                    },
                    x: {
                        ticks: { 
                            color: 'rgba(255, 255, 255, 0.62)',
                            maxRotation: 45,
                            minRotation: 45
                        },
                        grid: { display: false }
                    }
                }
            }
        });
    }
    
    /**
     * Render doughnut chart - User activity distribution
     */
    renderDoughnutChart(stats) {
        const canvas = document.getElementById('doughnutChart');
        if (!canvas) return;
        
        const ctx = canvas.getContext('2d');
        
        // Calculate time-based distribution
        const today = stats.today?.total || 0;
        const week = (stats.week?.total || 0) - today;
        const month = (stats.month?.total || 0) - (stats.week?.total || 0);
        const older = (stats.total || 0) - (stats.month?.total || 0);
        
        if (this.charts.doughnut) {
            this.charts.doughnut.destroy();
        }
        
        this.charts.doughnut = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Today', 'This Week', 'This Month', 'Older'],
                datasets: [{
                    data: [today, week, month, older],
                    backgroundColor: [
                        '#7CB6FF',
                        '#5A9EE8',
                        '#4A8ED8',
                        '#3A7EC8'
                    ],
                    borderWidth: 2,
                    borderColor: '#070A12'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        labels: {
                            color: 'rgba(255, 255, 255, 0.92)',
                            font: { size: 12 }
                        }
                    },
                    title: {
                        display: true,
                        text: 'Activity Distribution',
                        color: 'rgba(255, 255, 255, 0.92)',
                        font: { size: 16, weight: 'bold' }
                    }
                }
            }
        });
    }
    
    /**
     * Update all charts with new data
     */
    async updateCharts() {
        try {
            const stats = await this.fetchStats();
            
            this.renderLineChart(stats);
            this.renderPieChart(stats);
            this.renderBarChart(stats);
            this.renderDoughnutChart(stats);
            
            // Update stats numbers
            this.updateStatsDisplay(stats);
        } catch (error) {
            console.error('Failed to update charts:', error);
        }
    }
    
    /**
     * Update statistics display
     */
    updateStatsDisplay(stats) {
        const elements = {
            'totalRefreshes': stats.total || 0,
            'successRate': (stats.success_rate || 0) + '%',
            'avgResponseTime': (stats.avg_response_time || 0) + 'ms',
            'activeUsers': stats.active_users || 0,
            'todayTotal': stats.today?.total || 0,
            'weekTotal': stats.week?.total || 0,
            'monthTotal': stats.month?.total || 0
        };
        
        for (const [id, value] of Object.entries(elements)) {
            const element = document.getElementById(id);
            if (element) {
                element.textContent = value;
            }
        }
    }
    
    /**
     * Start auto-refresh every 30 seconds
     */
    startAutoRefresh() {
        this.refreshInterval = setInterval(() => {
            this.updateCharts();
        }, 30000);
    }
    
    /**
     * Stop auto-refresh
     */
    stopAutoRefresh() {
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
            this.refreshInterval = null;
        }
    }
    
    /**
     * Destroy all charts
     */
    destroy() {
        this.stopAutoRefresh();
        
        for (const chart of Object.values(this.charts)) {
            if (chart) {
                chart.destroy();
            }
        }
        
        this.charts = {};
    }
}

// Export for use
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AnalyticsCharts;
}
