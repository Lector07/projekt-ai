<script setup lang="ts">
import { ref, reactive, watch, computed } from 'vue';
import axios from 'axios';
import draggable from 'vuedraggable';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from '@/components/ui/accordion';
import { ResizableHandle, ResizablePanel, ResizablePanelGroup } from '@/components/ui/resizable';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import Icon from '@/components/Icon.vue';
import { useToast } from 'primevue/usetoast';

const toast = useToast();

const props = defineProps<{
    modelValue: boolean;
    activeFilters: object;
}>();

const emit = defineEmits(['update:modelValue']);

const reportLoading = ref(false);
const pdfUrl = ref<string | null>(null);
let currentPdfBlobUrl: string | null = null;

const availableFields = [
    { field: 'id', header: 'ID Wizyty', type: 'numeric' },
    { field: 'appointment_datetime', header: 'Data wizyty', type: 'date' },
    { field: 'status_translated', header: 'Status', type: 'text' },
    { field: 'patient_name', header: 'Pacjent', type: 'text' },
    { field: 'doctor_full_name', header: 'Lekarz', type: 'text' },
    { field: 'procedure_name', header: 'Zabieg', type: 'text' },
    { field: 'procedure_base_price', header: 'Cena (PLN)', type: 'numeric' },
    { field: 'patient_notes', header: 'Notatki pacjenta', type: 'text' },
];

const reportConfig = reactive({
    title: 'Zestawienie Wizyt',
    orientation: 'PORTRAIT',
    companyInfo: {
        name: 'Klinika Chirurgii Plastycznej "Projekt AI"',
        address: 'ul. Medyczna 1',
        postalCode: '00-001',
        city: 'Warszawa',
        taxId: '123-456-78-90'
    },
    footerLeftText: 'Klinika "Projekt AI"',
    columns: availableFields.map(f => ({
        field: f.field,
        header: f.header,
        visible: !['id', 'patient_notes'].includes(f.field),
        width: -1,
        format: f.type === 'numeric' ? '#,##0.00' : (f.type === 'date' ? 'yyyy-MM-dd HH:mm' : null),
        groupCalculation: f.type === 'numeric' ? 'SUM' : 'NONE',
    })),
    groups: [] as Array<{ field: string; label: string; showFooter: boolean }>,
    pageFooterEnabled: true,
});

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

const refreshPreview = async () => {
    reportLoading.value = true;
    if (currentPdfBlobUrl) URL.revokeObjectURL(currentPdfBlobUrl);

    const finalConfig = JSON.parse(JSON.stringify(reportConfig));
    finalConfig.groups = finalConfig.groups
        .filter((g: { field: string }) => g.field)
        .map((g: { field: string; showFooter: boolean }) => {
            const fieldDef = availableFields.find(f => f.field === g.field);
            const header = fieldDef ? fieldDef.header : g.field;
            return {
                ...g,
                label: `"${header}: " + $F{${g.field}}`
            };
        });


    try {
        const response = await axios.post('/api/v1/admin/appointments/report', {
            config: finalConfig,
            filters: props.activeFilters
        }, { responseType: 'blob' });

        const blob = new Blob([response.data], { type: 'application/pdf' });
        currentPdfBlobUrl = URL.createObjectURL(blob);
        pdfUrl.value = currentPdfBlobUrl;
        toast.add({ severity: 'success', summary: 'Sukces', detail: 'Podgląd odświeżony.', life: 2000 });
    } catch (error) {
        console.error('Błąd podglądu:', error);
        toast.add({ severity: 'error', summary: 'Błąd', detail: 'Nie udało się odświeżyć podglądu.', life: 3000 });
        pdfUrl.value = null;
    } finally {
        reportLoading.value = false;
    }
};

const downloadReport = () => {
    if (!pdfUrl.value) {
        toast.add({ severity: 'warn', summary: 'Brak podglądu', detail: 'Najpierw odśwież podgląd.', life: 3000 });
        return;
    }
    const link = document.createElement('a');
    link.href = pdfUrl.value;
    link.setAttribute('download', `${reportConfig.title.replace(/\s/g, '_')}.pdf`);
    document.body.appendChild(link);
    link.click();
    link.remove();
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
                    Dostosuj raport i zobacz podgląd na żywo. Raport jest generowany na podstawie aktualnie ustawionych filtrów.
                </DialogDescription>
            </DialogHeader>

            <div class="flex-grow min-h-0">
                <ResizablePanelGroup direction="horizontal" class="h-full w-full">
                    <ResizablePanel :default-size="80" :min-size="30">
                        <div class="flex flex-col h-full p-4 pr-2 ">
                            <Accordion type="single" collapsible class="w-full" default-value="item-1">
                                <AccordionItem value="item-1">
                                    <AccordionTrigger>Opcje Główne</AccordionTrigger>
                                    <AccordionContent class="pt-4">
                                        <ScrollArea class="max-h-64 overflow-y-auto">
                                            <div class="grid grid-cols-4 items-center gap-4">
                                                <Label for="report-title" class="text-right">Tytuł Raportu</Label>
                                                <Input id="report-title" v-model="reportConfig.title" class="col-span-3" />
                                            </div>
                                            <div class="grid grid-cols-4 mt-2 mb-2 items-center gap-4">
                                                <Label for="orientation-checkbox" class="text-right">Orientacja</Label>
                                                <div class="col-span-3 flex items-center space-x-2">
                                                    <Checkbox id="orientation-checkbox" class="data-[state=checked]:bg-nova-accent data-[state=unchecked]:bg-nova-light border-nova-accent" v-model:checked="isLandscape" />
                                                    <Label for="orientation-checkbox">Pozioma</Label>
                                                </div>
                                            </div>
                                            <div class="grid grid-cols-4 mt-2 mb-2 items-center gap-4">
                                                <Label class="text-right">Stopka</Label>
                                                <div class="flex items-center space-x-2 col-span-3">
                                                    <Checkbox id="page-footer" class="data-[state=checked]:bg-nova-accent data-[state=unchecked]:bg-nova-light border-nova-accent" v-model:checked="reportConfig.pageFooterEnabled" />
                                                    <label for="page-footer" class="text-sm font-medium leading-none">Dołącz stopkę</label>
                                                </div>
                                            </div>
                                            <div v-if="reportConfig.pageFooterEnabled" class="grid grid-cols-4 mt-2 items-start gap-4">
                                                <Label for="footer-text" class="text-right pt-2">Tekst w stopce</Label>
                                                <textarea id="footer-text" v-model="reportConfig.footerLeftText" class="col-span-3 w-full rounded-md border border-input bg-background px-3 py-2 text-sm" rows="2"></textarea>
                                            </div>
                                        </ScrollArea>
                                    </AccordionContent>
                                </AccordionItem>
                                <AccordionItem value="item-2">
                                    <AccordionTrigger>Kolumny</AccordionTrigger>
                                    <AccordionContent class="pt-4">
                                        <ScrollArea class="max-h-66 overflow-y-auto">
                                            <div class="grid grid-cols-5 gap-x-4 gap-y-2 items-center font-semibold text-sm mb-2 ml-20">

                                                <span>Widoczna</span>
                                                <span class="col-span-2 ml-2">Nagłówek</span>
                                                <span class="text-center">Szer.</span>
                                                <span class="text-center">Format</span>
                                            </div>
                                            <draggable v-model="reportConfig.columns" item-key="field" handle=".drag-handle" ghost-class="ghost-class">
                                                <template #item="{ element: col, index }">
                                                    <div class="grid grid-cols-6 gap-x-1 gap-y-2 items-center mt-2">
                                                        <div class="drag-handle cursor-move p-2 text-gray-400 hover:text-gray-700 dark:hover:text-white">
                                                            <Icon name="grip" size="18" />
                                                        </div>
                                                        <Checkbox v-model:checked="col.visible" class="data-[state=checked]:bg-nova-accent data-[state=unchecked]:bg-nova-light border-nova-accent" />
                                                        <Input v-model="col.header" class="col-span-2 text-xs h-8"/>
                                                        <Input v-model.number="col.width" type="number" class="text-xs h-8 text-center" placeholder="auto"/>
                                                        <Input v-model="col.format" class="text-xs h-8" placeholder="np. #,##0.00"/>
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
                                            <draggable v-model="reportConfig.groups" item-key="index" handle=".drag-handle" ghost-class="ghost-class">
                                                <template #item="{ element: group, index }">
                                                    <div class="p-2 border rounded-md mb-2 bg-gray-50 dark:bg-gray-800 flex items-start space-x-2">
                                                        <div class="drag-handle cursor-move p-2 text-gray-400 hover:text-gray-700 dark:hover:text-white">
                                                            <Icon name="grip" size="18" />
                                                        </div>
                                                        <div class="flex-grow">
                                                            <div class="grid grid-cols-2 gap-4">
                                                                <div>
                                                                    <Label :for="`group-field-${index}`">Grupuj po polu</Label>
                                                                    <select v-model="group.field" :id="`group-field-${index}`" class="w-full mt-1 rounded-md border border-input bg-background px-3 py-2 text-sm">
                                                                        <option v-for="field in availableFields" :key="field.field" :value="field.field">{{ field.header }}</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="flex justify-between items-center mt-2">
                                                                <div class="flex items-center space-x-2">
                                                                    <Checkbox :id="`group-footer-${index}`" v-model:checked="group.showFooter" />
                                                                    <label :for="`group-footer-${index}`" class="text-sm">Pokaż podsumowanie</label>
                                                                </div>
                                                                <Button variant="destructive" size="sm" @click="removeGroup(index)">Usuń</Button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </template>
                                            </draggable>
                                            <Button @click="addGroup" class="mt-2 w-full bg-nova-primary hover:bg-nova-accent">Dodaj nową grupę</Button>
                                        </ScrollArea>
                                    </AccordionContent>
                                </AccordionItem>
                            </Accordion>
                        </div>
                    </ResizablePanel>
                    <ResizableHandle with-handle />
                    <ResizablePanel :default-size="80">
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
                <Button @click="downloadReport" :disabled="!pdfUrl || reportLoading" class="bg-green-600 hover:bg-green-700 text-white">
                    <Icon name="download" class="mr-2" size="16"/>
                    Pobierz PDF
                </Button>
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
