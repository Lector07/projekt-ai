export function buildReportPayload(reportConfig, subreportConfigs) {
  // ...existing code...
  return {
    columns: reportConfig.columns, // teraz mogą zawierać typ 'subreport'
    subreportConfigs: subreportConfigs,
    // ...existing code...
  };
}

