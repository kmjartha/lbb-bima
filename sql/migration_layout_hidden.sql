-- Add hidden-sections support to rapor template.
ALTER TABLE report_templates
  ADD COLUMN layout_hidden_json JSON NULL AFTER layout_json;
