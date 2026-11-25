<?php

/**
 * admin/audit_log.php - the audit trail (controller)
 *
 * FR-60: "The system shall maintain an audit log recording all critical
 *        Administrator actions including account changes, order status
 *        updates, medicine deletions, and report generation events."
 *
 * The log is read-only. Nothing in the project ever edits or deletes a row
 * here, which is what makes it worth trusting.
 */

require_once __DIR__ . '/../app/bootstrap.php';

requireRole('admin');

$auditLogModel = new AuditLog($pdo);

render('admin/audit_log', [
    'pageTitle' => 'Audit log',
    'entries' => $auditLogModel->getRecent(200),
    'totalEntries' => $auditLogModel->countAll(),
], 'admin');