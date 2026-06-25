<?php
// ethioareb/includes/header.php - CMS Header
$settings = getSettings();
$stats = getDashboardStats();
$unread = getUnreadCount();
$current_page = basename($_SERVER['PHP_SELF']);
$current_page = str_replace('.php', '', $current_page);
if ($current_page == 'system_settings') $current_page = 'settings';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CMS - <?php echo $settings['site_name'] ?? 'Ethio Areb'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>ethioareb/assets/css/ethioareb.css">
    <style>
        * { font-family: 'Inter', sans-serif; }
        h1, h2, h3, h4 { font-family: 'Poppins', sans-serif; }
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
            transition: all 0.3s ease;
        }
        .stat-card:hover {
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
            transform: translateY(-2px);
        }
        .stat-number { font-size: 2rem; font-weight: 700; font-family: 'Poppins', sans-serif; }
        .table-container {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
            overflow-x: auto;
        }
        .table-container table { width: 100%; border-collapse: collapse; }
        .table-container th {
            text-align: left;
            padding: 0.75rem 1rem;
            font-weight: 600;
            color: #374151;
            border-bottom: 2px solid #E5E7EB;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .table-container td {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #F3F4F6;
            font-size: 0.9rem;
        }
        .table-container tr:hover td { background: #F9FAFB; }
        .badge {
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
        }
        .badge-success { background: #D1FAE5; color: #065F46; }
        .badge-danger { background: #FEE2E2; color: #991B1B; }
        .badge-warning { background: #FEF3C7; color: #92400E; }
        .badge-info { background: #DBEAFE; color: #1E40AF; }
        .badge-primary { background: #DBEAFE; color: #1E40AF; }
        .btn-primary {
            background: #0B3D91;
            color: white;
            padding: 10px 24px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            text-decoration: none;
        }
        .btn-primary:hover {
            background: #092c6e;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(11, 61, 145, 0.3);
            color: white;
        }
        .btn-danger {
            background: #EF4444;
            color: white;
            padding: 10px 24px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            text-decoration: none;
        }
        .btn-danger:hover {
            background: #DC2626;
            color: white;
        }
        .btn-success {
            background: #22C55E;
            color: white;
            padding: 10px 24px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            text-decoration: none;
        }
        .btn-success:hover {
            background: #16A34A;
            color: white;
        }
        .btn-warning {
            background: #F59E0B;
            color: white;
            padding: 10px 24px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            text-decoration: none;
        }
        .btn-warning:hover {
            background: #D97706;
            color: white;
        }
        .form-control {
            width: 100%;
            padding: 10px 16px;
            border: 1px solid #D1D5DB;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            outline: none;
            border-color: #0B3D91;
            box-shadow: 0 0 0 3px rgba(11, 61, 145, 0.1);
        }
        label { font-weight: 600; color: #374151; display: block; margin-bottom: 0.5rem; }
        .alert {
            padding: 12px 20px;
            border-radius: 10px;
            margin-bottom: 1rem;
        }
        .alert-success { background: #D1FAE5; border: 1px solid #A7F3D0; color: #065F46; }
        .alert-danger { background: #FEE2E2; border: 1px solid #FCA5A5; color: #991B1B; }
        .alert-warning { background: #FEF3C7; border: 1px solid #FCD34D; color: #92400E; }
        .alert-info { background: #DBEAFE; border: 1px solid #93C5FD; color: #1E40AF; }
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            padding: 1rem;
        }
        .modal-box {
            background: white;
            border-radius: 24px;
            padding: 2rem;
            max-width: 600px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
        }
        @media (max-width: 768px) {
            .table-container { padding: 0.5rem; }
            .table-container th, .table-container td { padding: 0.5rem; font-size: 0.8rem; }
            .modal-box { padding: 1.5rem; margin: 1rem; }
        }
    </style>
</head>
<body>