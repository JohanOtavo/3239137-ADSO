/**
 * An input that can show the issue the API reported for its own field.
 *
 * The API already names the field it rejected, so the form does not repeat the
 * validation: it points at what the server said. Two copies of a rule are two
 * rules that drift.
 */
export function Field({ label, name, value, onChange, issues, type = 'text', ...rest }) {
  const issue = issues?.[name];

  return (
    <div>
      <label htmlFor={name}>{label}</label>
      <input
        id={name}
        name={name}
        type={type}
        value={value}
        onChange={(event) => onChange(name, event.target.value)}
        aria-invalid={issue === undefined ? undefined : true}
        {...rest}
      />
      {issue !== undefined && <p className="field-issue">{issue}</p>}
    </div>
  );
}
