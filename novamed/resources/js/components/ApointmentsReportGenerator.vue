<script setup lang="ts">
import {ref, reactive, watch, computed} from 'vue';
import axios from 'axios';
import draggable from 'vuedraggable';

import {Button} from '@/components/ui/button';
import {Input} from '@/components/ui/input';
import {Label} from '@/components/ui/label';
import {Checkbox} from '@/components/ui/checkbox';
import {Accordion, AccordionContent, AccordionItem, AccordionTrigger} from '@/components/ui/accordion';
import {ResizableHandle, ResizablePanel, ResizablePanelGroup} from '@/components/ui/resizable';
import {ScrollArea} from '@/components/ui/scroll-area';
import {Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle} from '@/components/ui/dialog';
import Icon from '@/components/Icon.vue';
import {useToast} from 'primevue/usetoast';
import {Separator} from "@/components/ui/separator"

const toast = useToast();

const props = defineProps<{
    modelValue: boolean;
    activeFilters: object;
}>();

const emit = defineEmits(['update:modelValue']);

const reportLoading = ref(false);
const pdfUrl = ref<string | null>(null);
let currentPdfBlobUrl: string | null = null;
const subreportEditorVisible = ref(false);
const editingSubreportField = ref('');

interface ColumnConfig {
    field: string;
    header: string;
    visible: boolean;
    width: number | null | undefined;
    format: string | null | undefined;
    groupCalculation: string;
}

interface ReportConfig {
    title: string;
    orientation: 'PORTRAIT' | 'LANDSCAPE';
    theme: 'CLASSIC' | 'MODERN' | 'MINIMAL';
    companyInfo: object | null;
    footerLeftText: string | null;
    columns: ColumnConfig[];
    groups: Array<{ field: string; showFooter: boolean }>;
    pageFooterEnabled: boolean;
    summaryBandEnabled: boolean;
    formattingOptions: object;
    subreportConfigs: {
        [key: string]: Omit<ReportConfig, 'subreportConfigs'>;
    };
}

const availableFields = [
    {field: 'id', header: 'ID Wizyty', type: 'numeric'},
    {field: 'appointment_datetime', header: 'Data wizyty', type: 'date'},
    {field: 'status_translated', header: 'Status', type: 'text'},
    {field: 'patient_name', header: 'Pacjent', type: 'text'},
    {field: 'doctor_full_name', header: 'Lekarz', type: 'text'},
    {field: 'procedure_name', header: 'Zabieg', type: 'text'},
    {field: 'procedure_base_price', header: 'Cena (PLN)', type: 'numeric'},
    {field: 'patient_notes', header: 'Notatki pacjenta', type: 'text'},
];

const reportConfig = reactive<ReportConfig>({
    title: 'Zestawienie Wizyt',
    orientation: 'PORTRAIT',
    theme: 'CLASSIC',
    companyInfo: {
        name: 'Klinika Chirurgii Plastycznej "Projekt AI"',
        address: 'ul. Medyczna 1',
        postalCode: '00-001',
        city: 'Warszawa',
        taxId: '123-456-78-90'
    },
    footerLeftText: 'eBudżet - ZSI "Sprawny Urząd" \nBUK Softres - www.softres.pl',
    columns: availableFields.map(f => ({
        field: f.field,
        header: f.header,
        visible: !['id', 'patient_notes', 'billing_details'].includes(f.field),
        width: -1,
        format: f.type === 'numeric' ? '#,##0.00' : (f.type === 'date' ? 'yyyy-MM-dd HH:mm' : null),
        groupCalculation: f.type === 'numeric' ? 'SUM' : 'NONE',
    })),
    groups: [] as Array<{ field: string; showFooter: boolean }>,
    pageFooterEnabled: true,
    summaryBandEnabled: false,

    formattingOptions: {
        zebraStripes: false,
        generateBookmarks: false,
        highlightRules: [
            {
                field: 'status_translated',
                operator: 'EQUALS',
                value: 'Anulowana',
                color: '#FFF0F0',
            }
        ]
    },

    subreportConfigs: {}
});

const highlightOperators = [
    {value: 'EQUALS', label: 'równa się'},
    {value: 'NOT_EQUALS', label: 'nie równa się'},
    {value: 'CONTAINS', label: 'zawiera'},
    {value: 'GREATER_THAN', label: 'większe niż'},
    {value: 'LESS_THAN', label: 'mniejsze niż'},
]

const isLandscape = computed({
    get: () => reportConfig.orientation === 'LANDSCAPE',
    set: (val) => {
        reportConfig.orientation = val ? 'LANDSCAPE' : 'PORTRAIT';
    }
});

const addGroup = () => {
    if (availableFields.length > 0) {
        const defaultField = availableFields[0];
        reportConfig.groups.push({
            field: defaultField.field,
            showFooter: false
        });
    }
};

const removeGroup = (index: number) => {
    reportConfig.groups.splice(index, 1);
};

function normalizeColor(color: string): string {
    if (!color) return '#FFFFFF';
    if (color.startsWith('#')) color = color.slice(1);
    if (color.length === 3) {
        color = color.split('').map(c => c + c).join('');
    }
    if (color.length !== 6) return '#FFFFFF';
    return `#${color.toUpperCase()}`;
}

function coerceNumericRuleValue(value: unknown): number | unknown {
    if (typeof value === 'number') return value;
    if (value == null) return value;
    const str = String(value).trim();
    if (str === '') return value;
    const normalized = str.replace(/\s/g, '').replace(',', '.');
    const parsed = Number(normalized);
    return Number.isNaN(parsed) ? value : parsed;
}

const configureSubreport = (fieldName: string) => {
    if (!reportConfig.subreportConfigs[fieldName]) {
        reportConfig.subreportConfigs[fieldName] = {
            title: "",
            orientation: 'PORTRAIT',
            companyInfo: null,
            footerLeftText: null,
            columns: [
                {field: 'item', header: 'Pozycja', visible: true, width: -1, format: null, groupCalculation: 'NONE'},
                {field: 'quantity', header: 'Ilość', visible: true, width: 80, format: null, groupCalculation: 'NONE'},
                {field: 'price', header: 'Cena', visible: true, width: 100, format: '#,##0.00', groupCalculation: 'SUM'}
            ],
            groups: [],
            pageFooterEnabled: false,
            formattingOptions: {zebraStripes: true, generateBookmarks: false, highlightRules: []},
        };
    }

    editingSubreportField.value = fieldName;
    subreportEditorVisible.value = true;
};

const availableThemes = [
    { value: 'CLASSIC', label: 'Klasyczny' },
    { value: 'MODERN', label: 'Nowoczesny' },
    { value: 'MINIMAL', label: 'Minimalistyczny' }
];

const refreshPreview = async () => {
    reportLoading.value = true;
    if (currentPdfBlobUrl) {
        URL.revokeObjectURL(currentPdfBlobUrl);
    }

    // Stwórz głęboką kopię konfiguracji, aby bezpiecznie ją modyfikować
    const finalConfig = JSON.parse(JSON.stringify(reportConfig));

    if (finalConfig.subreportConfigs) {
        for (const key in finalConfig.subreportConfigs) {
            delete finalConfig.subreportConfigs[key].subreportConfigs;
        }
    }
    // Upewnij się również, że główny obiekt jest poprawny
    if (!finalConfig.subreportConfigs || Array.isArray(finalConfig.subreportConfigs)) {
        finalConfig.subreportConfigs = {};
    }

    // Pozostałe operacje normalizacyjne
    if (finalConfig.groups) {
        finalConfig.groups = finalConfig.groups
            .filter((g: { field: string }) => g.field)
            .map((g: { field: string; showFooter: boolean }) => {
                const fieldDef = availableFields.find(f => f.field === g.field);
                const header = fieldDef ? fieldDef.header : g.field;
                return {...g, label: `"${header}: " + $F{${g.field}}`};
            });
    }
    if (finalConfig.formattingOptions && finalConfig.formattingOptions.highlightRules) {
        finalConfig.formattingOptions.highlightRules.forEach((rule: any) => {
            rule.color = normalizeColor(rule.color);
        });
    }
    finalConfig.fieldTypes = Object.fromEntries(availableFields.map(f => [f.field, f.type]));

    console.log("OSTATECZNY PAYLOAD WYSYŁANY Z FRONTENDU:", JSON.stringify({
        config: finalConfig,
        filters: props.activeFilters
    }, null, 2));

    try {
        const response = await axios.post('/api/v1/admin/appointments/report', {
            config: finalConfig,
            filters: props.activeFilters
        }, {responseType: 'blob'});

        const blob = new Blob([response.data], {type: 'application/pdf'});
        currentPdfBlobUrl = URL.createObjectURL(blob);
        pdfUrl.value = currentPdfBlobUrl;
        toast.add({severity: 'success', summary: 'Sukces', detail: 'Podgląd odświeżony.', life: 2000});
    } catch (error) {
        console.error('Błąd podglądu:', error);
        let errorText = 'Wystąpił nieznany błąd.';
        if ((error as any).response?.data) {
            try {
                // Próba odczytania błędu jako tekst (może być JSON lub zwykły tekst)
                const errorData = (error as any).response.data;
                if (errorData instanceof Blob) {
                    const text = await errorData.text();
                    try {
                        const jsonError = JSON.parse(text);
                        errorText = jsonError.details || jsonError.error || text;
                    } catch {
                        errorText = text;
                    }
                } else if (typeof errorData === 'object') {
                    errorText = errorData.details || errorData.error || JSON.stringify(errorData);
                } else {
                    errorText = errorData.toString();
                }
            } catch (e) {
                console.error('Nie udało się sparsować odpowiedzi błędu:', e);
            }
        }
        toast.add({severity: 'error', summary: 'Błąd generowania raportu', detail: errorText, life: 7000});
        pdfUrl.value = null;
    } finally {
        reportLoading.value = false;
    }
};


const downloadReport = () => {
    if (!pdfUrl.value) {
        toast.add({severity: 'warn', summary: 'Brak podglądu', detail: 'Najpierw odśwież podgląd.', life: 3000});
        return;
    }
    const link = document.createElement('a');
    link.href = pdfUrl.value;
    link.setAttribute('download', `${reportConfig.title.replace(/\s/g, '_')}.pdf`);
    document.body.appendChild(link);
    link.click();
    link.remove();
};

const addHighlightRule = () => {
    reportConfig.formattingOptions.highlightRules.push({
        field: 'procedure_base_price',
        operator: 'GREATER_THAN',
        value: '1000',
        color: '#FFF0F0',
        id: `rule-${Date.now()}`
    });
};

const removeHighlightRule = (index: number) => {
    reportConfig.formattingOptions.highlightRules.splice(index, 1);
};

watch(() => props.modelValue, (newValue) => {
    if (newValue) {
        refreshPreview();
    }
});
</script>

<template>
    <Dialog :open="props.modelValue" @update:open="emit('update:modelValue', $event)">
        <DialogContent class="w-[80vw] max-w-none h-[90vh] flex flex-col">
            <DialogHeader>
                <DialogTitle>Zaawansowany Generator Raportów</DialogTitle>
                <DialogDescription>
                    Dostosuj raport i zobacz podgląd na żywo. Raport jest generowany na podstawie aktualnie ustawionych
                    filtrów.
                </DialogDescription>
            </DialogHeader>

            <div class="flex-grow min-h-0">
                <ResizablePanelGroup direction="horizontal" class="h-full w-full">
                    <ResizablePanel :default-size="90" :min-size="30">
                        <div class="flex flex-col h-full p-4 pr-2 ">
                            <Accordion type="single" collapsible class="w-full" default-value="item-1">
                                <AccordionItem value="item-1">
                                    <AccordionTrigger>Opcje Główne</AccordionTrigger>
                                    <AccordionContent class="pt-4">
                                        <ScrollArea class="max-h-64 overflow-y-auto">
                                            <div class="grid grid-cols-4 items-center gap-4">
                                                <Label for="report-title" class="text-right">Tytuł Raportu</Label>
                                                <Input id="report-title" v-model="reportConfig.title"
                                                       class="col-span-3"/>
                                            </div>
                                            <div class="grid grid-cols-4 mt-2 mb-2 items-center gap-4">
                                                <Label for="orientation-checkbox" class="text-right">Orientacja</Label>
                                                <div class="col-span-3 flex items-center space-x-2">
                                                    <Checkbox id="orientation-checkbox"
                                                              class="data-[state=checked]:bg-nova-accent data-[state=unchecked]:bg-nova-light border-nova-accent"
                                                              v-model:checked="isLandscape"/>
                                                    <Label for="orientation-checkbox">Pozioma</Label>
                                                </div>
                                            </div>
                                            <div class="grid grid-cols-4 mt-2 mb-2 items-center gap-4">
                                                <Label class="text-right">Stopka</Label>
                                                <div class="flex items-center space-x-2 col-span-3">
                                                    <Checkbox id="page-footer"
                                                              class="data-[state=checked]:bg-nova-accent data-[state=unchecked]:bg-nova-light border-nova-accent"
                                                              v-model:checked="reportConfig.pageFooterEnabled"/>
                                                    <label for="page-footer" class="text-sm font-medium leading-none">Dołącz
                                                        stopkę</label>
                                                </div>
                                            </div>
                                            <div class="grid grid-cols-4 mt-2 mb-2 items-center gap-4">
                                                <Label class="text-right">Podsumowanie</Label>
                                                <div class="flex items-center space-x-2 col-span-3">
                                                    <Checkbox id="summary-band"
                                                              class="data-[state=checked]:bg-nova-accent data-[state=unchecked]:bg-nova-light border-nova-accent"
                                                              v-model:checked="reportConfig.summaryBandEnabled"/>
                                                    <label for="summary-band" class="text-sm font-medium leading-none">Dołącz
                                                        podsumowanie na końcu</label>
                                                </div>
                                            </div>

                                            <Separator class="my-4"/>

                                            <div class="grid grid-cols-4 mt-2 items-start gap-4">
                                                <Label for="company-info" class="text-right pt-2">Nazwa
                                                    jednostki</Label>
                                                <Input id="company-info" v-model="reportConfig.companyInfo.name"
                                                       class="col-span-3 w-full rounded-md border border-input bg-background px-3 py-2 text-sm "
                                                       rows="2"></Input>
                                            </div>
                                            <div class="grid grid-cols-4 mt-2 items-start gap-4">
                                                <Label for="company-address" class="text-right pt-2">Adres
                                                    jednostki</Label>
                                                <Input id="company-address" v-model="reportConfig.companyInfo.address"
                                                       class="col-span-3 w-full rounded-md border border-input bg-background px-3 py-2 text-sm "
                                                       rows="2"></Input>
                                            </div>
                                            <div class="grid grid-cols-4 mt-2 items-start gap-4">
                                                <Label for="company-postalcode" class="text-right pt-2">Kod
                                                    pocztowy</Label>
                                                <Input id="company-postalcode"
                                                       v-model="reportConfig.companyInfo.postalCode" class="col-span-3 w-full rounded-md border border-input
    bg-background px-3 py-2 text-sm " rows="2"></Input>
                                            </div>
                                            <div class="grid grid-cols-4 mt-2 items-start gap-4">
                                                <Label for="company-city" class="text-right pt-2">Miasto</Label>
                                                <Input id="company-city" v-model="reportConfig.companyInfo.city"
                                                       class="col-span-3 w-full rounded-md border border-input bg-background px-3 py-2 text-sm "
                                                       rows="2"></Input>
                                            </div>
                                            <div class="grid grid-cols-4 mt-2 items-start gap-4">
                                                <Label for="company-taxid" class="text-right pt-2">NIP</Label>
                                                <Input id="company-taxid" v-model="reportConfig.companyInfo.taxId"
                                                       class="col-span-3 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                                       rows="2"></Input>
                                            </div>

                                            <Separator class="my-4"/>

                                            <div v-if="reportConfig.pageFooterEnabled"
                                                 class="grid grid-cols-4 mt-2 items-start gap-4">
                                                <Label for="footer-text" class="text-right pt-2">Tekst w stopce</Label>
                                                <textarea id="footer-text" v-model="reportConfig.footerLeftText"
                                                          class="col-span-3 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                                          rows="2"></textarea>
                                            </div>

                                            <div class="grid grid-cols-4 items-center gap-4 mt-2">
                                                <Label for="report-theme" class="text-right">Motyw wizualny</Label>
                                                <select v-model="reportConfig.theme" id="report-theme" class="col-span-2 w-full mt-1 rounded-md border border-input bg-background px-3 py-2 text-sm">
                                                    <option v-for="theme in availableThemes" :key="theme.value" :value="theme.value">
                                                        {{ theme.label }}
                                                    </option>
                                                </select>
                                            </div>
                                        </ScrollArea>
                                    </AccordionContent>
                                </AccordionItem>
                                <AccordionItem value="item-2">
                                    <AccordionTrigger>Kolumny</AccordionTrigger>
                                    <AccordionContent class="pt-4">
                                        <ScrollArea class="max-h-66 overflow-y-auto">
                                            <div
                                                class="grid grid-cols-5 gap-x-4 gap-y-2 items-center font-semibold text-sm mb-2 ml-20">

                                                <span>Widoczna</span>
                                                <span class="col-span-2 ml-2">Nagłówek</span>
                                                <span class="text-center">Szer.</span>
                                                <span class="text-center">Format</span>
                                            </div>
                                            <draggable v-model="reportConfig.columns" item-key="field"
                                                       handle=".drag-handle" ghost-class="ghost-class">
                                                <template #item="{ element: col, index }">
                                                    <div class="grid grid-cols-6 gap-x-1 gap-y-2 items-center mt-2">
                                                        <div
                                                            class="drag-handle cursor-move p-2 text-gray-400 hover:text-gray-700 dark:hover:text-white">
                                                            <Icon name="grip" size="18"/>
                                                        </div>
                                                        <Checkbox v-model:checked="col.visible"
                                                                  class="data-[state=checked]:bg-nova-accent data-[state=unchecked]:bg-nova-light border-nova-accent"/>
                                                        <Input v-model="col.header" class="col-span-2 text-xs h-8"/>
                                                        <Input v-model.number="col.width" type="number"
                                                               class="text-xs h-8 text-center" placeholder="auto"/>
                                                        <Input v-model="col.format" class="text-xs h-8"
                                                               placeholder="np. #,##0.00"/>
                                                    </div>
                                                </template>
                                            </draggable>
                                        </ScrollArea>
                                    </AccordionContent>
                                </AccordionItem>
                                <AccordionItem value="item-3">
                                    <AccordionTrigger>Grupowanie (przeciągnij)</AccordionTrigger>
                                    <AccordionContent class="pt-4">
                                        <ScrollArea class="max-h-64 overflow-y-auto">
                                            <draggable v-model="reportConfig.groups" item-key="index"
                                                       handle=".drag-handle" ghost-class="ghost-class">
                                                <template #item="{ element: group, index }">
                                                    <div
                                                        class="p-2 border rounded-md mb-2 bg-gray-50 dark:bg-gray-800 flex items-start space-x-2">
                                                        <div
                                                            class="drag-handle cursor-move p-2 text-gray-400 hover:text-gray-700 dark:hover:text-white">
                                                            <Icon name="grip" size="18"/>
                                                        </div>
                                                        <div class="flex-grow">
                                                            <div class="grid grid-cols-2 gap-4">
                                                                <div>
                                                                    <Label :for="`group-field-${index}`">Grupuj po
                                                                        polu</Label>
                                                                    <select v-model="group.field"
                                                                            :id="`group-field-${index}`"
                                                                            class="w-full mt-1 rounded-md border border-input bg-background px-3 py-2 text-sm">
                                                                        <option v-for="field in availableFields"
                                                                                :key="field.field" :value="field.field">
                                                                            {{ field.header }}
                                                                        </option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="flex justify-between items-center mt-2">
                                                                <div class="flex items-center space-x-2">
                                                                    <Checkbox :id="`group-footer-${index}`"
                                                                              class="data-[state=checked]:bg-nova-accent data-[state=unchecked]:bg-nova-light border-nova-accent"
                                                                              v-model:checked="group.showFooter"/>
                                                                    <label :for="`group-footer-${index}`"
                                                                           class="text-sm">Pokaż podsumowanie</label>
                                                                </div>
                                                                <Button variant="destructive" size="sm"
                                                                        @click="removeGroup(index)">Usuń
                                                                </Button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </template>
                                            </draggable>
                                            <Button @click="addGroup" class="mt-2 w-full" variant="outline">Dodaj nową
                                                grupę
                                            </Button>
                                        </ScrollArea>
                                    </AccordionContent>
                                </AccordionItem>
                                <AccordionItem value="item-4">
                                    <AccordionTrigger>Formatowanie i Eksport</AccordionTrigger>
                                    <AccordionContent class="space-y-4 pt-4">
                                        <ScrollArea class="max-h-54 overflow-y-auto">

                                            <div class="flex items-center space-x-6">
                                                <div class="flex items-center space-x-2">
                                                    <Checkbox id="zebra-stripes"
                                                              class="data-[state=checked]:bg-nova-accent data-[state=unchecked]:bg-nova-light border-nova-accent"
                                                              v-model:checked="reportConfig.formattingOptions.zebraStripes"/>
                                                    <label for="zebra-stripes" class="text-sm font-medium">Paski
                                                        zebry</label>
                                                </div>
                                                <div class="flex items-center space-x-2">
                                                    <Checkbox id="generate-bookmarks"
                                                              class="data-[state=checked]:bg-nova-accent data-[state=unchecked]:bg-nova-light border-nova-accent"
                                                              v-model:checked="reportConfig.formattingOptions.generateBookmarks"/>
                                                    <label for="generate-bookmarks" class="text-sm font-medium">Generuj
                                                        zakładki PDF</label>
                                                </div>
                                            </div>
                                            <div class="pt-4 mt-2 border-t dark:border-gray-700">
                                                <Label class="font-semibold">Reguły podświetlania wierszy</Label>
                                                <div
                                                    v-for="(rule, index) in reportConfig.formattingOptions.highlightRules"
                                                    :key="rule.id"
                                                    class="p-2 border rounded-md mt-2 space-y-2 bg-gray-50 dark:bg-gray-800">
                                                    <div class="flex items-center space-x-2">
                                                        <span class="text-sm font-bold">JEŚLI</span>
                                                        <select v-model="rule.field"
                                                                class="flex-1 rounded-md border-input bg-background px-2 py-1 text-sm">
                                                            <option v-for="field in availableFields" :key="field.field"
                                                                    :value="field.field">{{ field.header }}
                                                            </option>
                                                        </select>
                                                        <select v-model="rule.operator"
                                                                class="rounded-md border-input bg-background px-2 py-1 text-sm">
                                                            <option v-for="op in highlightOperators" :key="op.value"
                                                                    :value="op.value">{{ op.label }}
                                                            </option>
                                                        </select>
                                                        <template
                                                            v-if="(availableFields.find(f => f.field === rule.field)?.type === 'numeric') && rule.operator !== 'CONTAINS'">
                                                            <Input v-model="(rule as any).value" type="number"
                                                                   placeholder="Wartość liczbowa"
                                                                   class="flex-1 text-sm h-8"/>
                                                        </template>
                                                        <template v-else>
                                                            <Input v-model="(rule as any).value" placeholder="Wartość"
                                                                   class="flex-1 text-sm h-8"/>
                                                        </template>
                                                    </div>
                                                    <div class="flex items-center space-x-2">
                                                        <span class="text-sm font-bold">WTEDY</span>
                                                        <Label for="color-picker" class="text-sm">kolor tła:</Label>
                                                        <Input v-model="rule.color" type="color" id="color-picker"
                                                               class="h-8 w-12 p-1"/>
                                                        <div class="flex-grow"></div>
                                                        <Button variant="destructive" size="icon"
                                                                @click="removeHighlightRule(index)">
                                                            <Icon name="trash" size="14"/>
                                                        </Button>
                                                    </div>
                                                </div>
                                                <Button @click="addHighlightRule" class="mt-2 w-full" variant="outline">
                                                    <Icon name="plus" class="mr-2" size="8"/>
                                                    Dodaj regułę podświetlania
                                                </Button>
                                            </div>
                                        </ScrollArea>
                                    </AccordionContent>
                                </AccordionItem>
                                <AccordionItem value="item-5">
                                    <AccordionTrigger>Podraporty</AccordionTrigger>
                                    <AccordionContent class="space-y-4 pt-4">
                                        <div class="p-2 border-t-1">
                                            <h4 class="font-semibold mt-2">Podraport dla: </h4>
                                            <div class="mt-2 ">
                                                <Button @click="configureSubreport('billing_details')"
                                                        class="bg-nova-primary text-nova-light hover:bg-nova-accent hover:text-nova-light"
                                                        size="sm" variant="outline">
                                                    Konfiguruj kolumny podraportu
                                                </Button>
                                            </div>
                                        </div>
                                    </AccordionContent>
                                </AccordionItem>
                            </Accordion>
                        </div>
                    </ResizablePanel>
                    <ResizableHandle with-handle/>
                    <ResizablePanel :default-size="70" :min-size="30">
                        <div class="flex flex-col h-full items-center justify-center bg-gray-200 dark:bg-gray-800">
                            <div v-if="reportLoading" class="flex flex-col items-center text-gray-500">
                                <Icon name="loader-2" class="animate-spin h-12 w-12"/>
                                <p class="mt-2">Generowanie podglądu...</p>
                            </div>
                            <div v-else-if="pdfUrl" class="w-full h-full">
                                <iframe :src="pdfUrl" class="w-full h-full border-0" title="Podgląd Raportu"></iframe>
                            </div>
                            <div v-else class="text-center text-gray-500 p-4">
                                <p>Podgląd raportu pojawi się tutaj.</p>
                                <p class="text-sm mt-1">Jeśli wystąpił błąd, sprawdź konsolę przeglądarki (F12).</p>
                            </div>
                        </div>
                    </ResizablePanel>
                </ResizablePanelGroup>
            </div>
            <div class="mt-4 pt-4 border-t dark:border-gray-700 flex items-center justify-between">
                <Button @click="refreshPreview" :disabled="reportLoading" variant="outline">
                    <Icon v-if="reportLoading" name="loader-2" class="animate-spin mr-2" size="16"/>
                    <Icon v-else name="refresh-cw" class="mr-2" size="16"/>
                    Odśwież podgląd
                </Button>
                <Button @click="downloadReport" :disabled="!pdfUrl || reportLoading"
                        class="bg-green-600 hover:bg-green-700 text-white">
                    <Icon name="download" class="mr-2" size="16"/>
                    Pobierz PDF
                </Button>
            </div>
        </DialogContent>
    </Dialog>
    <Dialog :open="subreportEditorVisible" @update:open="subreportEditorVisible = $event">
        <DialogContent class="w-[40vw] max-w-none h-[70vh] flex flex-col">
            <DialogHeader>
                <DialogTitle>Edytor Kolumn Subraportu: {{ editingSubreportField }}</DialogTitle>
                <DialogDescription>
                    Przeciągnij, aby zmienić kolejność. Ustaw widoczność, nagłówki i szerokości kolumn.
                </DialogDescription>
            </DialogHeader>

            <div v-if="editingSubreportField && reportConfig.subreportConfigs[editingSubreportField]"
                 class="flex-grow min-h-0 py-4">
                <ScrollArea class="h-full">
                    <div class="grid grid-cols-5 gap-8 items-center font-semibold text-sm mb-2 ml-20">
                        <span class="text-center">Widoczna</span>
                        <span class="col-span-2 text-center ">Nagłówek</span>
                        <span class="text-center ml-18">Szer.</span>
                    </div>
                    <draggable
                        v-model="reportConfig.subreportConfigs[editingSubreportField].columns"
                        item-key="field"
                        handle=".drag-handle"
                        ghost-class="ghost-class"
                    >
                        <template #item="{ element: col }">
                            <div class="grid grid-cols-5 gap-x-2 gap-y-2 items-center mt-2 ml-4">
                                <div
                                    class="drag-handle cursor-move p-2 text-gray-400 hover:text-gray-700 dark:hover:text-white">
                                    <Icon name="grip" size="18"/>
                                </div>
                                <Checkbox v-model:checked="col.visible"
                                          class="data-[state=checked]:bg-nova-accent data-[state=unchecked]:bg-nova-light border-nova-accent"/>
                                <Input v-model="col.header" class="col-span-2 text-xs h-8"/>
                                <Input v-model.number="col.width" type="number" class="text-xs h-8 text-center"
                                       placeholder="auto"/>
                            </div>
                        </template>
                    </draggable>
                </ScrollArea>
            </div>

            <div class="mt-4 pt-4 border-t flex justify-end">
                <Button @click="subreportEditorVisible = false" variant="secondary">Zamknij</Button>
            </div>
        </DialogContent>
    </Dialog>
</template>

<style scoped>
.ghost-class {
    opacity: 0.5;
    background: #c8ebfb;
}
</style>
