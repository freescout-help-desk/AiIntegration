const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const nodes = new Map();
function node(id) {
    if (!nodes.has(id)) nodes.set(id, {
        disabled: false, classes: new Set(), content: 'Previous summary',
        hasClass(name) { return this.classes.has(name); },
        prop(name, value) { if (arguments.length === 1) return this[name]; this[name] = value; return this; },
        button(state) { this.state = state; return this; },
        html(value) { this.content = value; return this; },
        addClass(name) { this.classes.add(name); return this; },
        removeClass(name) { this.classes.delete(name); return this; }
    });
    return nodes.get(id);
}
let requests = 0, pending, errors = 0;
const context = {
    $: node, getGlobalAttr: () => 1, laroute: {route: () => '/ajax'},
    fsAjax(data, url, success, enabled, failure) { requests++; pending = {data, success, failure}; },
    isAjaxSuccess: response => response.status === 'success',
    showAjaxError: response => { assert.equal(typeof response, 'object'); errors++; }
};
vm.createContext(context);
vm.runInContext(fs.readFileSync(__dirname+'/../Public/js/module.js', 'utf8'), context);
assert.equal(requests, 0, 'Loading a saved summary never triggers AI');
const refresh = node('refresh'); refresh.classes.add('aii-refresh-summary');
context.aiiSummarize(refresh);
assert.equal(pending.data.refresh, 1);
context.aiiSummarize(refresh);
assert.equal(requests, 1, 'Repeated clicks during loading are ignored');
pending.failure();
assert.equal(refresh.disabled, false);
assert.equal(node('#aii_summary').content, 'Previous summary');
assert.equal(errors, 1);
context.aiiSummarize(refresh);
pending.success({status: 'success', html: 'Rendered saved summary'});
assert.equal(node('#aii_summary').content, 'Rendered saved summary');
assert(node('#aii_summarize').hasClass('hidden'));
context.aiiSummarize(refresh);
pending.success({status: 'error'});
assert.equal(node('#aii_summary').content, 'Rendered saved summary');
assert.equal(refresh.disabled, false);
console.log('PASS: no automatic calls, manual refresh, duplicate clicks, network and API failure recovery');
