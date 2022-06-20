/**
 * WordPress dependencies
 */
import {
	__experimentalItemGroup as ItemGroup,
	__experimentalHStack as HStack,
	__experimentalSpacer as Spacer,
	__experimentalVStack as VStack,
	Button,
	Card,
	CardBody,
	CardDivider,
	CardFooter,
	CardMedia,
	FlexItem,
	Flex,
} from '@wordpress/components';
import { isRTL, __ } from '@wordpress/i18n';
import { chevronLeft, chevronRight, reusableBlock } from '@wordpress/icons';
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { IconWithCurrentColor } from './icon-with-current-color';
import { NavigationButtonAsItem } from './navigation-button';
import ContextMenu from './context-menu';
import StylesPreview from './preview';
import { useRandomizer } from './hooks';

function ScreenRoot() {
	const { variations } = useSelect( ( select ) => {
		return {
			variations:
				select(
					coreStore
				).__experimentalGetCurrentThemeGlobalStylesVariations(),
		};
	}, [] );

	const [ randomizeTheme ] = useRandomizer();

	return (
		<Card size="small">
			<Flex direction="column">
				<FlexItem>
					<CardBody>
						<VStack spacing={ 4 }>
							<Card>
								<CardMedia>
									<StylesPreview />
								</CardMedia>
							</Card>
							{ !! variations?.length && (
								<ItemGroup>
									<NavigationButtonAsItem path="/variations">
										<HStack justify="space-between">
											<FlexItem>
												{ __( 'Browse styles' ) }
											</FlexItem>
											<IconWithCurrentColor
												icon={
													isRTL()
														? chevronLeft
														: chevronRight
												}
											/>
										</HStack>
									</NavigationButtonAsItem>
								</ItemGroup>
							) }
							<ContextMenu />
						</VStack>
					</CardBody>
					<CardDivider />
					<CardBody>
						<Spacer
							as="p"
							paddingTop={ 2 }
							/*
							 * 13px matches the text inset of the NavigationButton (12px padding, plus the width of the button's border).
							 * This is an ad hoc override for this particular instance only and should be reconsidered before making into a pattern.
							 */
							paddingX="13px"
							marginBottom={ 4 }
						>
							{ __(
								'Customize the appearance of specific blocks for the whole site.'
							) }
						</Spacer>
						<ItemGroup>
							<NavigationButtonAsItem path="/blocks">
								<HStack justify="space-between">
									<FlexItem>{ __( 'Blocks' ) }</FlexItem>
									<IconWithCurrentColor
										icon={
											isRTL() ? chevronLeft : chevronRight
										}
									/>
								</HStack>
							</NavigationButtonAsItem>
						</ItemGroup>
					</CardBody>
					<CardDivider />
				</FlexItem>
				<CardFooter>
					<Button
						icon={ reusableBlock }
						label={ __( 'Randomize colors' ) }
						onClick={ randomizeTheme }
					>
						{ __( 'Randomize' ) }
					</Button>
				</CardFooter>
			</Flex>
		</Card>
	);
}

export default ScreenRoot;
