-- brevo_monthly_stats used to be unique on (month_start, month_end), so each
-- weekly run inserted another to-date snapshot of the current month instead of
-- updating it. Keep only the most complete row per month, then key on
-- month_start alone. Run backfill_monthly_stats.php afterwards to refetch
-- missing months and finalize partial ones.

DELETE older FROM brevo_monthly_stats older
JOIN brevo_monthly_stats newer
  ON newer.month_start = older.month_start
 AND (newer.month_end > older.month_end
      OR (newer.month_end = older.month_end AND newer.id > older.id));

ALTER TABLE brevo_monthly_stats
    DROP INDEX uniq_month,
    ADD UNIQUE KEY uniq_month (month_start);
