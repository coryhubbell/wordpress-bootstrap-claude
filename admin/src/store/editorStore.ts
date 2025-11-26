/**
 * Editor State Management Store
 * Using Zustand for lightweight, performant state management
 */

import { create } from 'zustand';
import { devtools, persist } from 'zustand/middleware';
import type {
  EditorState,
  Framework,
  CorrectionSuggestion,
  TooltipData,
  UserPreferences,
} from '@/types';

interface EditorStoreState {
  // Editor State
  editor: EditorState;

  // Corrections
  corrections: CorrectionSuggestion[];
  activeCorrections: string[]; // IDs of corrections being displayed

  // Tooltips
  tooltips: TooltipData[];
  activeTooltip: string | null;

  // User Preferences
  preferences: UserPreferences;

  // Loading States
  isLoading: boolean;
  isTranslating: boolean;
  isFetchingCorrections: boolean;

  // Actions
  setSourceFramework: (framework: Framework) => void;
  setTargetFramework: (framework: Framework) => void;
  setSourceCode: (code: string) => void;
  setTranslatedCode: (code: string) => void;
  setIsDirty: (dirty: boolean) => void;

  // Correction Actions
  addCorrection: (correction: CorrectionSuggestion) => void;
  removeCorrection: (id: string) => void;
  clearCorrections: () => void;
  applyCorrection: (id: string) => void;

  // Tooltip Actions
  showTooltip: (tooltip: TooltipData) => void;
  hideTooltip: (id: string) => void;
  clearTooltips: () => void;

  // Translation Actions
  translateCode: () => Promise<void>;

  // Preferences Actions
  updatePreferences: (preferences: Partial<UserPreferences>) => void;

  // Reset
  reset: () => void;
}

const defaultEditorState: EditorState = {
  sourceFramework: 'bootstrap',
  targetFramework: 'elementor',
  sourceCode: '',
  translatedCode: '',
  isDirty: false,
  isTranslating: false,
  lastSaved: null,
};

const defaultPreferences: UserPreferences = {
  theme: 'light',
  editorFontSize: 14,
  editorFontFamily: 'Fira Code, monospace',
  showMinimap: true,
  enableAI: true,
  enableRealTimeCorrections: true,
  autoSave: true,
  notifications: {
    corrections: true,
    translations: true,
    errors: true,
  },
};

export const useEditorStore = create<EditorStoreState>()(
  devtools(
    persist(
      (set, get) => ({
        // Initial State
        editor: defaultEditorState,
        corrections: [],
        activeCorrections: [],
        tooltips: [],
        activeTooltip: null,
        preferences: defaultPreferences,
        isLoading: false,
        isTranslating: false,
        isFetchingCorrections: false,

        // Editor Actions
        setSourceFramework: (framework) =>
          set((state) => ({
            editor: { ...state.editor, sourceFramework: framework, isDirty: true },
          })),

        setTargetFramework: (framework) =>
          set((state) => ({
            editor: { ...state.editor, targetFramework: framework, isDirty: true },
          })),

        setSourceCode: (code) =>
          set((state) => ({
            editor: { ...state.editor, sourceCode: code, isDirty: true },
          })),

        setTranslatedCode: (code) =>
          set((state) => ({
            editor: { ...state.editor, translatedCode: code },
          })),

        setIsDirty: (dirty) =>
          set((state) => ({
            editor: { ...state.editor, isDirty: dirty },
          })),

        // Correction Actions
        addCorrection: (correction) =>
          set((state) => ({
            corrections: [...state.corrections, correction],
          })),

        removeCorrection: (id) =>
          set((state) => ({
            corrections: state.corrections.filter((c) => c.id !== id),
            activeCorrections: state.activeCorrections.filter((cid) => cid !== id),
          })),

        clearCorrections: () =>
          set({
            corrections: [],
            activeCorrections: [],
          }),

        applyCorrection: (id) => {
          const correction = get().corrections.find((c) => c.id === id);
          if (!correction || !correction.autoFix) return;

          const { sourceCode } = get().editor;
          const lines = sourceCode.split('\n');

          // Apply the fix
          const line = lines[correction.line - 1];
          if (line) {
            const before = line.substring(0, correction.column);
            const after = line.substring(correction.endColumn);
            lines[correction.line - 1] = before + correction.autoFix.replacement + after;
          }

          const updatedCode = lines.join('\n');

          set((state) => ({
            editor: { ...state.editor, sourceCode: updatedCode, isDirty: true },
          }));

          // Remove the applied correction
          get().removeCorrection(id);
        },

        // Tooltip Actions
        showTooltip: (tooltip) =>
          set((state) => ({
            tooltips: [...state.tooltips, tooltip],
            activeTooltip: tooltip.id,
          })),

        hideTooltip: (id) =>
          set((state) => ({
            tooltips: state.tooltips.filter((t) => t.id !== id),
            activeTooltip: state.activeTooltip === id ? null : state.activeTooltip,
          })),

        clearTooltips: () =>
          set({
            tooltips: [],
            activeTooltip: null,
          }),

        // Translation Actions
        translateCode: async () => {
          const { sourceCode, sourceFramework, targetFramework } = get().editor;

          if (!sourceCode.trim()) {
            alert('Please enter some code to translate');
            return;
          }

          set({ isTranslating: true });

          try {
            // Call WordPress REST API
            const restUrl = (window as any).wpbcData?.restUrl || '/wp-json/wpbc/v2/';
            const response = await fetch(`${restUrl}translate`, {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': (window as any).wpbcData?.nonce || '',
              },
              body: JSON.stringify({
                source: sourceFramework,
                target: targetFramework,
                content: sourceCode,
              }),
            });

            // Get response text first
            const responseText = await response.text();

            // Check if response is JSON
            let data;
            try {
              data = JSON.parse(responseText);
            } catch (e) {
              throw new Error(`Invalid JSON response from server. Got: ${responseText.substring(0, 200)}`);
            }

            if (!response.ok) {
              throw new Error(data.message || data.error || `HTTP ${response.status}: ${response.statusText}`);
            }

            if (!data.result && !data.translated_code) {
              throw new Error('Translation succeeded but no result was returned');
            }

            set((state) => ({
              editor: {
                ...state.editor,
                translatedCode: data.result || data.translated_code || '',
                lastSaved: new Date(),
              },
              isTranslating: false,
            }));

            alert('Translation successful!');
          } catch (error) {
            console.error('Translation error:', error);
            set({ isTranslating: false });

            const errorMessage = error instanceof Error ? error.message : 'Unknown error';
            alert(`Translation failed:\n\n${errorMessage}\n\nCheck browser console (F12) for details.`);
          }
        },

        // Preferences Actions
        updatePreferences: (preferences) =>
          set((state) => ({
            preferences: { ...state.preferences, ...preferences },
          })),

        // Reset
        reset: () =>
          set({
            editor: defaultEditorState,
            corrections: [],
            activeCorrections: [],
            tooltips: [],
            activeTooltip: null,
            isLoading: false,
            isTranslating: false,
            isFetchingCorrections: false,
          }),
      }),
      {
        name: 'wpbc-editor-storage',
        partialize: (state) => ({
          preferences: state.preferences,
          editor: {
            sourceFramework: state.editor.sourceFramework,
            targetFramework: state.editor.targetFramework,
          },
        }),
      }
    )
  )
);
