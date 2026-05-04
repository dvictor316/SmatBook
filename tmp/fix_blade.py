path = '/mnt/c/Users/victor/Desktop/smat-book/resources/views/Reports/Reports/balance-sheet.blade.php'
with open(path, 'r', encoding='utf-8') as f:
    lines = f.readlines()

new_block = [
    '        @if(isset($allBranches) && $allBranches->count() > 1)\n',
    '        <div class="bs-filter-group">\n',
    '            <span class="bs-filter-label">Branch</span>\n',
    "            <select name=\"branch_id\" onchange=\"this.form.submit()\">\n",
    "                <option value=\"all\" {{ ($activeBranch['scope'] ?? '') === 'all' ? 'selected' : '' }}>All Branches</option>\n",
    "                @foreach($allBranches as $br)\n",
    "                    <option value=\"{{ $br['id'] }}\" {{ ($activeBranch['id'] ?? '') == $br['id'] ? 'selected' : '' }}>\n",
    "                        {{ $br['name'] }}\n",
    "                    </option>\n",
    "                @endforeach\n",
    "            </select>\n",
    "        </div>\n",
    "        @endif\n",
    '        <div class="bs-filter-actions">\n',
    '            <button type="submit" class="bs-btn-run">Run Report</button>\n',
    '        </div>\n',
    '    </form>\n',
]

# Lines 718-721 (0-indexed 717-720) were wiped by sed - replace them
lines[717:721] = new_block

with open(path, 'w', encoding='utf-8') as f:
    f.writelines(lines)
print('DONE, total lines:', len(lines))
