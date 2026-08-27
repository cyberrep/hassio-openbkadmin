const fs = require("fs");
const path = require("path");

describe("OpenBeken 0.6.5 state source", () => {
  const source = fs.readFileSync(
    path.join(__dirname, "../../resources/js/OpenBeken.js"),
    "utf8",
  );

  test("does not issue the invalid Ch read-all command", () => {
    expect(source).not.toContain('this._doAjax(ip, id, "Ch"');
  });

  test("uses Status 0 POWER fields", () => {
    expect(source).toContain('"Status 0"');
    expect(source).toContain('`POWER${device_relais}`');
    expect(source).toContain("data.StatusSTS.POWER");
  });
});
