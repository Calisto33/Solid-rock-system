<?php
    // This is includes/header.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parent Portal</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* All your CSS from the previous file goes here */
        :root {
            --primary-color: #3b82f6;
            --background-light: #f8f9fa;
            --background-white: #ffffff;
            --text-dark: #212529;
            --text-muted: #6c757d;
            --border-color: #dee2e6;
            
            --blue-light: #e0f2fe;    --blue-dark: #0ea5e9;
            --orange-light: #fff7ed;  --orange-dark: #f97316;
            --red-light: #ffebee;      --red-dark: #ef4444;
            --green-light: #f0fdf4;   --green-dark: #22c55e;
            
            --status-cleared: #16a34a; --status-present: #16a34a;
            --status-pending: #f59e0b; --status-overdue: #dc2626; --status-absent: #dc2626;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background-color: var(--background-light); color: var(--text-dark); display: flex; height: 100vh; overflow: hidden; }
        .dashboard-wrapper { display: flex; width: 100%; height: 100%; }

        /* --- Sidebar --- */
        .sidebar { width: 260px; background-color: var(--background-white); border-right: 1px solid var(--border-color); padding: 1.5rem; display: flex; flex-direction: column; }
        .sidebar-header { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 2.5rem; }
        .sidebar-header img { width: 40px; height: 40px; }
        .sidebar-header h1 { font-size: 1.25rem; font-weight: 700; }
        .sidebar-nav { flex-grow: 1; overflow-y: auto; }
        .sidebar-nav h3 { font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin: 1.5rem 0 0.5rem; }
        .sidebar-nav ul { list-style: none; }
        .sidebar-nav a { display: flex; align-items: center; padding: 0.8rem; border-radius: 0.5rem; text-decoration: none; color: var(--text-muted); font-weight: 500; margin-bottom: 0.25rem; }
        .sidebar-nav a:hover { background-color: var(--background-light); color: var(--text-dark); }
        .sidebar-nav a.active { background-color: var(--primary-color); color: white; }
        .sidebar-nav a i { font-size: 1.1rem; width: 24px; text-align: center; margin-right: 0.75rem; }
        .sidebar-footer { margin-top: auto; padding-top: 1.5rem; border-top: 1px solid var(--border-color); }
        
        .logout-btn { display: flex; align-items: center; justify-content: center; width: 100%; padding: 0.8rem; border-radius: 0.5rem; text-decoration: none; background-color: var(--red-light); color: var(--red-dark); font-weight: 600; transition: all 0.2s ease-in-out; }
        .logout-btn i { margin-right: 0.5rem; }
        .logout-btn:hover { background-color: var(--red-dark); color: white; transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.1); }


        /* --- Main Content --- */
        .main-content { flex: 1; background-color: var(--background-white); padding: 2rem; overflow-y: auto; }
        .main-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .header-title h2 { font-weight: 700; font-size: 1.75rem; }
        .header-title p { color: var(--text-muted); }
        .header-actions { display: flex; align-items: center; gap: 1rem; }
        .user-profile { display: flex; align-items: center; gap: 1rem; }
        .user-profile .icon-button { background: none; border: none; font-size: 1.25rem; color: var(--text-muted); cursor: pointer; position: relative; }

        /* Notification Dropdown & Other Styles... */
        .notification-badge { position: absolute; top: -5px; right: -8px; background-color: var(--red-dark); color: white; width: 18px; height: 18px; border-radius: 50%; font-size: 0.7rem; font-weight: 600; display: flex; align-items: center; justify-content: center; border: 2px solid var(--background-white); }
        .notification-dropdown { display: none; position: absolute; top: 150%; right: 0; background-color: var(--background-white); border: 1px solid var(--border-color); border-radius: 0.5rem; box-shadow: 0 4px 12px rgba(0,0,0,0.1); width: 320px; z-index: 100; }
        .notification-dropdown.active { display: block; }
        .notification-dropdown-header { padding: 1rem; border-bottom: 1px solid var(--border-color); }
        .notification-list { list-style: none; max-height: 300px; overflow-y: auto; }
        .notification-list li { padding: 1rem; display: flex; gap: 1rem; }
        .notification-list li:not(:last-child) { border-bottom: 1px solid #f3f4f6; }
        .notification-list li.unread { background-color: var(--blue-light); }
        .notification-list .icon { font-size: 1.2rem; color: var(--primary-color); }
        .notification-list .message p { font-weight: 500; font-size: 0.9rem; margin-bottom: 0.25rem; }
        .notification-list .message span { font-size: 0.8rem; color: var(--text-muted); }
        .notification-dropdown-footer { padding: 0.75rem; border-top: 1px solid var(--border-color); text-align: center; }
        .notification-dropdown-footer a { color: var(--primary-color); text-decoration: none; font-weight: 500; }
        
        /* Other card and table styles... */
        .overview-cards { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.5rem; margin-bottom: 2rem; }
        .overview-card { padding: 1.5rem; border-radius: 0.75rem; display: flex; align-items: center; gap: 1.5rem; background-color: var(--background-white); border: 1px solid var(--border-color); }
        .activity-table table { width: 100%; border-collapse: collapse; background-color:var(--background-white); }
        .activity-table th, .activity-table td { padding: 1rem; text-align: left; border-bottom: 1px solid var(--border-color); }

        /* Responsive */
        @media (max-width: 1200px) { .overview-cards { grid-template-columns: repeat(2, 1fr); } .sidebar { width: 220px; } }
        @media (max-width: 768px) { body { flex-direction: column; height: auto; overflow: auto; } .sidebar { width: 100%; height: auto; border-right: none; border-bottom: 1px solid var(--border-color); } .main-content { padding: 1rem; } .main-header { flex-direction: column; align-items: flex-start; gap: 1rem; } .overview-cards { grid-template-columns: 1fr; } .activity-table { display: none; } }


        /* --- NEW: Styles for Events Page --- */
.page-title {
    font-size: 1.75rem;
    font-weight: 700;
    margin-bottom: 1.5rem;
    color: var(--text-dark);
    padding-bottom: 0.75rem;
    border-bottom: 1px solid var(--border-color);
}
.events-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
}
.event-card {
    background: var(--background-white);
    border-radius: 0.5rem;
    border: 1px solid var(--border-color);
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    transition: all 0.3s ease;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}
.event-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
}
.event-card-header {
    background-color: var(--primary-color);
    color: white;
    padding: 1rem 1.5rem;
}
.event-card-header h3 {
    margin: 0;
    font-size: 1.2rem;
    font-weight: 600;
}
.event-card-body {
    padding: 1.5rem;
    flex-grow: 1;
    display: flex;
    flex-direction: column;
}
.event-card-body p {
    flex-grow: 1;
    margin-bottom: 1rem;
    color: var(--text-muted);
}
.event-card-footer {
    margin-top: auto;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}
.event-timestamp {
    font-size: 0.8rem;
    color: var(--text-muted);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.download-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background-color: var(--green-dark);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 0.375rem;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.2s ease;
}
.download-btn:hover {
    background-color: #15803d;
}

/* --- NEW: Styles for Feedback Page --- */
.form-group {
    margin-bottom: 1.5rem;
}
.form-label {
    display: block;
    font-weight: 500;
    margin-bottom: 0.5rem;
    color: var(--text-dark);
}
.form-control {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid var(--border-color);
    border-radius: 0.5rem;
    font-family: inherit;
    font-size: 1rem;
    transition: all 0.2s ease;
}
.form-control:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
}
.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    border-radius: 0.5rem;
    font-weight: 600;
    text-decoration: none;
    border: none;
    cursor: pointer;
    font-size: 0.9rem;
    transition: all 0.2s ease;
}
.btn-submit {
    background-color: var(--primary-color);
    color: white;
}
.btn-submit:hover {
    background-color: #1d4ed8;
}
.message-box {
    padding: 1rem;
    border-radius: 0.5rem;
    margin-bottom: 1.5rem;
    border: 1px solid transparent;
}
.message-box.success {
    background-color: var(--green-light);
    color: #14532d;
    border-color: #bbf7d0;
}
.message-box.error {
    background-color: var(--red-light);
    color: #991b1b;
    border-color: #fecaca;
}
.feedback-table-wrapper {
    background-color: var(--background-white);
    border: 1px solid var(--border-color);
    border-radius: 0.5rem;
    overflow: hidden;
}
.status-reviewed { color: #1e40af; background-color: #dbeafe; }
.status-resolved { color: #166534; background-color: #dcfce7; }

/* --- NEW: Professional Styles for Feedback Page --- */
.feedback-grid {
    display: grid;
    grid-template-columns: 1fr 2fr; /* 1/3 for the form, 2/3 for the history */
    gap: 2rem;
}
.feedback-form-card .card-header,
.feedback-history-card .card-header {
    padding: 1.5rem;
    border-bottom: 1px solid var(--border-color);
}
.feedback-form-card .card-body,
.feedback-history-card .card-body {
    padding: 1.5rem;
}
.feedback-form-card h3,
.feedback-history-card h3 {
    font-size: 1.25rem;
    margin: 0;
}
.feedback-history-list {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}
.feedback-item {
    border: 1px solid var(--border-color);
    border-radius: 0.5rem;
    background-color: var(--background-light);
}
.feedback-item-header {
    padding: 1rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid var(--border-color);
}
.feedback-item-header .date {
    font-size: 0.85rem;
    color: var(--text-muted);
}
.feedback-item-body {
    padding: 1rem;
    line-height: 1.7;
}
.feedback-item-body p {
    white-space: pre-wrap; /* Respects line breaks in the feedback */
}
.admin-response {
    margin-top: 1rem;
    padding: 1rem;
    background-color: var(--blue-light);
    border-radius: 0.375rem;
    border-left: 4px solid var(--primary-color);
}
.admin-response strong {
    color: var(--text-dark);
}

/* Responsive adjustments for the new grid */
@media (max-width: 1024px) {
    .feedback-grid {
        grid-template-columns: 1fr; /* Stack columns on tablets and smaller */
    }
}
/* --- NEW: Professional Styles for Feedback Page --- */
.feedback-grid {
    display: grid;
    grid-template-columns: 1fr 2fr; /* 1/3 for the form, 2/3 for the history */
    gap: 2rem;
    align-items: flex-start;
}
.feedback-form-card .card-header,
.feedback-history-card .card-header {
    padding: 1.5rem;
    border-bottom: 1px solid var(--border-color);
}
.feedback-form-card .card-body,
.feedback-history-card .card-body {
    padding: 1.5rem;
}
.feedback-form-card h3,
.feedback-history-card h3 {
    font-size: 1.25rem;
    margin: 0;
    font-weight: 600;
}
.feedback-history-list {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}
.feedback-item {
    border: 1px solid var(--border-color);
    border-radius: 0.5rem;
    background-color: var(--background-light);
    transition: box-shadow 0.2s ease;
}
.feedback-item:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}
.feedback-item-header {
    padding: 1rem 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid var(--border-color);
}
.feedback-item-header .date {
    font-size: 0.85rem;
    color: var(--text-muted);
}
.feedback-item-body {
    padding: 1.5rem;
    line-height: 1.7;
}
.feedback-item-body p.user-feedback {
    white-space: pre-wrap; /* Respects line breaks in the feedback */
    color: var(--text-dark);
}
.admin-response {
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px dashed var(--border-color);
}
.admin-response strong {
    color: var(--text-dark);
    display: block;
    margin-bottom: 0.5rem;
}
.admin-response p {
    font-style: italic;
    color: var(--text-muted);
    white-space: pre-wrap;
}
.empty-feedback {
    text-align: center;
    padding: 3rem;
    color: var(--text-muted);
}
.empty-feedback i {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

/* Responsive adjustments for the new grid */
@media (max-width: 1024px) {
    .feedback-grid {
        grid-template-columns: 1fr; /* Stack columns on tablets and smaller */
    }
}

/* --- NEW: Styles for Fees Page --- */
.content-card {
    background: var(--background-white);
    border-radius: 0.75rem;
    border: 1px solid var(--border-color);
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    overflow: hidden;
}
.content-card .card-header {
    background: var(--primary-color);
    color: white;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.8rem;
}
.content-card .card-header .card-title {
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0;
}
.content-card .card-body {
    padding: 1.5rem;
}
.table-container {
    overflow-x: auto;
}
.fees-table {
    width: 100%;
    border-collapse: collapse;
}
.fees-table th, .fees-table td {
    padding: 1rem;
    text-align: left;
    border-bottom: 1px solid var(--border-color);
    white-space: nowrap;
}
.fees-table thead th {
    background-color: var(--background-light);
    font-weight: 600;
}
.fees-table tbody tr:hover {
    background-color: #f8f9fa;
}
.fees-table .amount {
    font-weight: 600;
}
.fees-table .amount-owed {
    color: var(--status-overdue);
    font-weight: 700;
}
.status-indicator {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.3rem 0.7rem;
    border-radius: 50px;
    font-weight: 500;
    font-size: 0.8rem;
}
.status-indicator.status-cleared {
    background-color: var(--green-light);
    color: var(--status-cleared);
}
.status-indicator.status-pending {
    background-color: #fffbeb;
    color: var(--status-pending);
}
.status-indicator.status-overdue {
    background-color: var(--red-light);
    color: var(--status-overdue);
}
.empty-state {
    text-align: center;
    padding: 3rem;
    color: var(--text-muted);
}
.empty-state i {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

/* --- NEW: Styles for Summary Cards & Action Buttons --- */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}
.stat-card {
    background-color: var(--background-white);
    padding: 1.5rem;
    border-radius: 0.75rem;
    border: 1px solid var(--border-color);
    display: flex;
    align-items: center;
    gap: 1.5rem;
}
.stat-card .icon-wrapper {
    font-size: 1.75rem;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}
.stat-card .icon-wrapper.red {
    background-color: var(--red-light);
    color: var(--red-dark);
}
.stat-card .icon-wrapper.green {
    background-color: var(--green-light);
    color: var(--green-dark);
}
.stat-card .stat-info .title {
    color: var(--text-muted);
    margin-bottom: 0.25rem;
}
.stat-card .stat-info .value {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--text-dark);
}
.btn-pay {
    background-color: var(--accent-color, #4CAF50);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 0.375rem;
    text-decoration: none;
    font-weight: 500;
    font-size: 0.85rem;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}
.btn-pay:hover {
    background-color: var(--accent-hover, #3D9140);
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}
.btn-cleared {
    background-color: var(--background-light);
    color: var(--text-muted);
    padding: 0.5rem 1rem;
    border-radius: 0.375rem;
    font-size: 0.85rem;
    font-style: italic;
}

/* --- NEW: Styles for Notices Page --- */
.notices-container {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
}
.notice-card {
    background: var(--background-white);
    border-radius: 0.5rem;
    border: 1px solid var(--border-color);
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
    border-left: 4px solid var(--primary-color);
}
.notice-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
}
.notice-card-header {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--border-color);
}
.notice-card-header h3 {
    margin: 0;
    font-size: 1.2rem;
    font-weight: 600;
    color: var(--primary-color);
}
.notice-card-body {
    padding: 1.5rem;
    flex-grow: 1;
    color: var(--text-muted);
    line-height: 1.7;
}
.notice-card-footer {
    padding: 1rem 1.5rem;
    font-size: 0.8rem;
    color: var(--text-muted);
    border-top: 1px solid var(--border-color);
    background-color: var(--background-light);
}

/* --- NEW: Styles for Student Details Page --- */
.dashboard-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 2rem;
}

.details-card {
    background: var(--background-white);
    border-radius: 0.75rem;
    border: 1px solid var(--border-color);
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    overflow: hidden;
}

.details-card-header {
    padding: 1.5rem;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.details-card-title {
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.8rem;
    color: var(--primary-color);
}

.details-card-body {
    padding: 1.5rem;
}

/* Student Info */
.student-profile-header {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.student-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background-color: var(--primary-color);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    color: var(--background-white);
    font-weight: bold;
}

.student-main-details .student-name {
    font-size: 1.8rem;
    font-weight: 700;
    color: var(--text-dark);
    margin-bottom: 0.25rem;
}

.student-main-details .student-class {
    font-size: 1.1rem;
    color: var(--text-muted);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

/* Subjects Table */
.details-table {
    width: 100%;
    border-collapse: collapse;
}

.details-table th,
.details-table td {
    padding: 1rem;
    text-align: left;
    border-bottom: 1px solid var(--border-color);
}

.details-table thead th {
    background-color: var(--background-light);
}

.details-table tbody tr:hover {
    background-color: var(--background-light);
}

/* Attendance Summary */
.attendance-summary-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: var(--background-light);
    border-radius: 0.75rem;
    padding: 1.5rem;
    text-align: center;
}

.stat-card .stat-icon {
    font-size: 2rem;
    margin-bottom: 1rem;
}

.stat-card .stat-value {
    font-size: 2rem;
    font-weight: 700;
    line-height: 1;
}

.stat-card .stat-label {
    font-size: 0.9rem;
    color: var(--text-muted);
    margin-top: 0.5rem;
}
.stat-card .icon-present { color: var(--status-present); }
.stat-card .icon-absent { color: var(--status-absent); }
.stat-card .icon-total { color: var(--primary-color); }
.stat-card .icon-rate { color: #f59e0b; }


/* Attendance Filters & Badges */
.filters {
    display: flex;
    gap: 0.8rem;
    align-items: center;
    margin-top: 1.5rem;
    margin-bottom: 1rem;
    flex-wrap: wrap;
}

.filter-btn {
    padding: 0.5rem 1rem;
    background-color: var(--background-white);
    border: 1px solid var(--border-color);
    border-radius: 30px;
    color: var(--text-muted);
    font-weight: 500;
    text-decoration: none;
    transition: all 0.2s ease;
    font-size: 0.9rem;
}

.filter-btn.active, .filter-btn:hover {
    background-color: var(--primary-color);
    color: var(--background-white);
    border-color: var(--primary-color);
}

.badge {
    padding: 0.4rem 0.8rem;
    border-radius: 30px;
    font-size: 0.85rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    text-transform: capitalize;
}
.badge-present { background-color: var(--green-light); color: var(--status-present); }
.badge-absent { background-color: var(--red-light); color: var(--status-absent); }
.badge-holiday { background-color: #fffbeb; color: #f59e0b; }

/* Responsive Adjustments for Details page */
@media screen and (max-width: 768px) {
    .student-profile-header {
        flex-direction: column;
        text-align: center;
    }
    .attendance-summary-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

/* --- NEW: Styles for Teachers Profiles Page --- */
.page-header-container {
    text-align: center;
    margin-bottom: 2rem;
    position: relative;
    padding-bottom: 1rem;
}
.page-header-container::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 80px;
    height: 4px;
    background-color: var(--primary-color);
    border-radius: 2px;
}
.page-header-container .page-subtitle {
    color: var(--text-muted);
    font-size: 1.1rem;
}

.search-filter-box {
    margin-bottom: 2rem;
    background-color: var(--background-white);
    padding: 1.5rem;
    border-radius: 0.5rem;
    border: 1px solid var(--border-color);
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    justify-content: space-between;
    align-items: center;
}
.search-box {
    flex: 1;
    min-width: 250px;
    position: relative;
}
.search-box input {
    width: 100%;
    padding: 0.75rem 1rem 0.75rem 2.5rem;
    border: 1px solid var(--border-color);
    border-radius: 0.5rem;
    font-size: 1rem;
}
.search-box i {
    position: absolute;
    left: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-muted);
}
.filter-options {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}
.filter-select {
    padding: 0.75rem 1rem;
    border: 1px solid var(--border-color);
    border-radius: 0.5rem;
    font-size: 1rem;
    background-color: var(--background-white);
}

.profiles-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 2rem;
}
.profile-card {
    background-color: var(--background-white);
    border-radius: 0.75rem;
    overflow: hidden;
    border: 1px solid var(--border-color);
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    transition: all 0.3s ease;
    position: relative;
}
.profile-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
}
.profile-banner {
    height: 80px;
    background: var(--primary-color);
}
.profile-image {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    object-fit: cover;
    border: 5px solid var(--background-white);
    position: absolute;
    left: 50%;
    transform: translateX(-50%);
    top: 20px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}
.profile-content {
    padding: 4.5rem 1.5rem 1.5rem;
    text-align: center;
}
.profile-name {
    font-size: 1.4rem;
    font-weight: 700;
    color: var(--text-dark);
    margin-bottom: 0.5rem;
}
.profile-tag {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background-color: var(--background-light);
    border-radius: 20px;
    font-size: 0.9rem;
    color: var(--text-muted);
    font-weight: 500;
}
.profile-tag i {
    color: var(--primary-color);
}
.profile-bio {
    margin: 1.5rem 0;
    font-size: 0.95rem;
    color: var(--text-muted);
}
.profile-actions {
    display: flex;
    justify-content: center;
    gap: 1rem;
}
.profile-actions .action-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: var(--background-light);
    color: var(--primary-color);
    transition: all 0.2s ease;
    text-decoration: none;
}
.profile-actions .action-btn:hover {
    background-color: var(--primary-color);
    color: var(--background-white);
    transform: scale(1.1);
}
    </style>
</head>
<body>
    <div class="dashboard-wrapper">